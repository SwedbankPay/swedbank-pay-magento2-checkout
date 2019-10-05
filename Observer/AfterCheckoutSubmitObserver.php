<?php

namespace SwedbankPay\Checkout\Observer;

use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use PayEx\Api\Service\Paymentorder\Request\GetCurrentPayment;
use PayEx\Api\Service\Paymentorder\Resource\Response\Data\GetCurrentPaymentInterface;
use SwedbankPay\Core\Model\Service as ClientService;
use SwedbankPay\Core\Helper\Order as OrderHelper;
use SwedbankPay\Core\Logger\Logger;
use SwedbankPay\Checkout\Helper\Config as ConfigHelper;
use SwedbankPay\Checkout\Helper\PaymentData;
use SwedbankPay\Checkout\Model\Ui\ConfigProvider;

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
     * @var Logger
     */
    protected $logger;

    public function __construct(
        ConfigHelper $configHelper,
        PaymentData $paymentData,
        ClientService $clientService,
        OrderHelper $orderHelper,
        Logger $logger
    ) {
        $this->configHelper = $configHelper;
        $this->paymentData = $paymentData;
        $this->clientService = $clientService;
        $this->orderHelper = $orderHelper;
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

        /** @var OrderInterface $order */
        $order = $observer->getEvent()->getData('order');

        /** @var OrderPaymentInterface $payment */
        $payment = $order->getPayment();

        $this->logger->debug('Method: ' . $payment->getMethod());

        if ($payment->getMethod() != ConfigProvider::CODE) {
            return;
        }

        $paymentData = $this->paymentData->getByOrder($order);

        /** @var GetCurrentPayment $currentPaymentRequest */
        $currentPaymentRequest = $this->clientService->init('Paymentorder', 'GetCurrentPayment');
        $currentPaymentRequest->setRequestEndpointVars($paymentData->getPaymentOrderId());

        /** @var GetCurrentPaymentInterface $currentPayment */
        $currentPayment = $currentPaymentRequest->send()->getResponseResource();
        $paymentData->setIntent($currentPayment->getPayment()->getIntent());
        $this->paymentData->update($paymentData);

        if ($paymentData->getIntent() == 'Sale') {
            $this->logger->debug('Intent is sale, creating invoice!');
            $this->orderHelper->createInvoice($order);
        }
    }
}
