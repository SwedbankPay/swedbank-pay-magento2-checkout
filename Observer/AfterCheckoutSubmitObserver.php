<?php

namespace SwedbankPay\Checkout\Observer;

use Exception;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use SwedbankPay\Api\Service\Paymentorder\Request\GetCurrentPayment;
use SwedbankPay\Api\Service\Paymentorder\Resource\Response\Data\GetCurrentPaymentInterface;
use SwedbankPay\Checkout\Api\Data\OrderInterface as PaymentOrderInterface;
use SwedbankPay\Checkout\Helper\Config as ConfigHelper;
use SwedbankPay\Checkout\Helper\PaymentData;
use SwedbankPay\Checkout\Model\Ui\ConfigProvider;
use SwedbankPay\Core\Helper\Order as OrderHelper;
use SwedbankPay\Core\Logger\Logger;
use SwedbankPay\Core\Model\Service as ClientService;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AfterCheckoutSubmitObserver implements ObserverInterface
{
    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * @var PaymentData
     */
    protected $paymentData;

    /**
     * @var ClientService
     */
    protected $clientService;

    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var Logger
     */
    protected $logger;

    public function __construct(
        ConfigHelper $configHelper,
        PaymentData $paymentData,
        ClientService $clientService,
        OrderHelper $orderHelper,
        OrderRepository $orderRepository,
        Logger $logger
    ) {
        $this->configHelper = $configHelper;
        $this->paymentData = $paymentData;
        $this->clientService = $clientService;
        $this->orderHelper = $orderHelper;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
    }

    /**
     * checkout_submit_all_after event handler.
     *
     * @param Observer $observer
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws Exception
     *
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function execute(Observer $observer)
    {
        $this->logger->debug('Checkout submit action observer called!');

        if (!$this->configHelper->isActive()) {
            return;
        }

        /** @var Order $order */
        $order = $observer->getEvent()->getData('order');

        if (!$order || !($this->paymentData->getByOrder($order) instanceof PaymentOrderInterface)) {
            return;
        }

        /** @var OrderPaymentInterface $payment */
        $payment = $order->getPayment();

        $this->logger->debug('Method: ' . $payment->getMethod());

        if ($payment->getMethod() != ConfigProvider::CODE) {
            return;
        }

        $paymentData = $this->paymentData->getByOrder($order);

        /** @var GetCurrentPayment $currentPaymentRequest */
        $currentPaymentRequest = $this->clientService->init('Paymentorder', 'GetCurrentPayment');
        $currentPaymentRequest->setPaymentOrderId($paymentData->getPaymentIdPath());

        /** @var GetCurrentPaymentInterface $currentPayment */
        $currentPayment = $currentPaymentRequest->send()->getResponseResource();
        $paymentData->setIntent($currentPayment->getPayment()->getIntent());
        $this->paymentData->update($paymentData);

        if ($paymentData->getIntent() == 'Sale') {
            $this->saveTransactionNumber($order, $currentPayment);

            $this->logger->debug('Intent is sale, creating invoice!');
            $this->orderHelper->createInvoice($order);
        }
    }

    /**
     * @param Order $order
     * @param GetCurrentPaymentInterface $currentPayment
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function saveTransactionNumber(Order $order, GetCurrentPaymentInterface $currentPayment)
    {
        $transactionsArray = $currentPayment->getPayment()->getTransactions()->__toArray();
        $transactionList = $transactionsArray['transaction_list'];

        foreach ($transactionList as $transaction) {
            if ($transaction['type'] == 'Sale' && $transaction['state'] == 'Completed') {
                $order->setData('swedbank_pay_transaction_number', $transaction['number']);
                $this->orderRepository->save($order);

                $this->logger->debug(
                    'Saved sale transaction number to order grid',
                    ['order_id' => $order->getEntityId(), 'transaction_no' => $transaction['number']]
                );

                break;
            }
        }
    }
}
