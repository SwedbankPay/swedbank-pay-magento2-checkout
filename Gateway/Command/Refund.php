<?php

namespace SwedbankPay\Checkout\Gateway\Command;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use SwedbankPay\Api\Client\Exception;
use SwedbankPay\Api\Service\Data\ResponseInterface;
use SwedbankPay\Api\Service\Paymentorder\Transaction\Resource\Request\Transaction;
use SwedbankPay\Api\Service\Paymentorder\Transaction\Resource\Request\TransactionObject;
use SwedbankPay\Checkout\Helper\Factory\OrderItemsFactory;
use SwedbankPay\Core\Exception\ServiceException;
use SwedbankPay\Core\Model\Service as ClientRequestService;
use SwedbankPay\Core\Exception\SwedbankPayException;
use SwedbankPay\Core\Logger\Logger;

use Magento\Sales\Model\Order as MageOrder;
use Magento\Payment\Gateway\Command;
use Magento\Framework\App\RequestInterface;
use SwedbankPay\Checkout\Helper\PaymentData;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Refund extends AbstractCommand
{
    /**
     * @var RequestInterface|object
     */
    protected $request;

    /**
     * @var PaymentData
     */
    protected $paymentData;

    /**
     * @var OrderRepositoryInterface
     */
    protected $mageOrderRepo;

    /**
     * @var OrderItemsFactory
     */
    protected $orderItemsFactory;

    /**
     * Refund constructor.
     *
     * @param ClientRequestService $requestService
     * @param RequestInterface $request
     * @param PaymentData $paymentData
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderItemsFactory $orderItemsFactory
     * @param Logger $logger
     * @param array $data
     */
    public function __construct(
        ClientRequestService $requestService,
        RequestInterface $request,
        PaymentData $paymentData,
        OrderRepositoryInterface $orderRepository,
        OrderItemsFactory $orderItemsFactory,
        Logger $logger,
        array $data = []
    ) {
        parent::__construct(
            $requestService,
            $logger,
            $data
        );

        $this->request = $request;
        $this->paymentData = $paymentData;
        $this->mageOrderRepo = $orderRepository;
        $this->orderItemsFactory = $orderItemsFactory;
    }

    /**
     * Refund command
     *
     * @param array $commandSubject
     *
     * @return null|Command\ResultInterface
     * @throws NoSuchEntityException
     * @throws SwedbankPayException
     * @throws ServiceException
     * @throws Exception
     */
    public function execute(array $commandSubject)
    {
        /** @var InfoInterface|object $payment */
        $payment = $commandSubject['payment']->getPayment();
        $amount = $commandSubject['amount'] + 0;

        /** @var MageOrder $order */
        $order = $payment->getOrder();
        $paymentOrder = $this->paymentData->getByOrder($order);
        $orderItems = $this->orderItemsFactory->createByOrder($order);

        $this->checkRemainingAmount('refund', $amount, $order, $paymentOrder);

        $transaction = new Transaction();
        $transaction->setDescription("Reversing the captured payment")
            ->setAmount($amount * 100)
            ->setVatAmount($order->getBaseTaxAmount() * 100)
            ->setPayeeReference($this->generateRandomString(30))
            ->setOrderItems($orderItems);

        $transactionObject = new TransactionObject();
        $transactionObject->setTransaction($transaction);

        $reversalRequest = $this->getRequestService(
            'Paymentorder/Transaction',
            'TransactionReversal',
            $transactionObject
        );
        $reversalRequest->setPaymentOrderId($paymentOrder->getPaymentIdPath());

        /** @var ResponseInterface $reversalResponse */
        $reversalResponse = $reversalRequest->send();

        $this->checkResponseResource('refund', $reversalResponse->getResponseResource(), $order, $paymentOrder);

        $reversalResponseData = $reversalResponse->getResponseData();

        $transactionResult = $this->getTransactionResult('refund', $reversalResponseData, $order, $paymentOrder);

        if ($transactionResult != 'complete') {
            $order->setStatus('swedbank_pay_pending');
            $this->mageOrderRepo->save($order);
            return null;
        }

        $this->paymentData->updateRemainingAmounts('refund', $amount, $paymentOrder);

        $order->setStatus('swedbank_pay_reversed');
        $this->mageOrderRepo->save($order);

        return null;
    }
}
