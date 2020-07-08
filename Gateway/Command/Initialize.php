<?php

namespace SwedbankPay\Checkout\Gateway\Command;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Model\InfoInterface;
use Magento\Store\Model\Store;
use SwedbankPay\Core\Model\Service as ClientRequestService;
use SwedbankPay\Core\Logger\Logger;

use Magento\Framework\DataObject;
use Magento\Payment\Gateway\Command;
use Magento\Sales\Model\Order;
use SwedbankPay\Checkout\Helper\Config as PaymentMenuConfig;
use SwedbankPay\Checkout\Helper\PaymentData;

class Initialize extends AbstractCommand
{
    const TYPE_AUTH = 'authorization';

    /**
     * @var PaymentData
     */
    protected $paymentData;

    /**
     * @var PaymentMenuConfig
     */
    protected $paymentMenuConfig;

    /**
     * Initialize constructor.
     *
     * @param PaymentMenuConfig $paymentMenuConfig
     * @param ClientRequestService $requestService
     * @param PaymentData $paymentData
     * @param Logger $logger
     * @param array $data
     */
    public function __construct(
        PaymentMenuConfig $paymentMenuConfig,
        ClientRequestService $requestService,
        PaymentData $paymentData,
        Logger $logger,
        array $data = []
    ) {
        parent::__construct(
            $requestService,
            $logger,
            $data
        );

        $this->paymentData = $paymentData;
        $this->paymentMenuConfig = $paymentMenuConfig;
    }

    /**
     * Initialize command
     *
     * @param array $commandSubject
     *
     * @return null|Command\ResultInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws NoSuchEntityException
     */
    public function execute(array $commandSubject)
    {
        /** @var InfoInterface|object $payment */
        $payment = $commandSubject['payment']->getPayment();

        /** @var DataObject|object $stateObject */
        $stateObject = $commandSubject['stateObject'];

        /** @var Order $order */
        $order = $payment->getOrder();

        $paymentQuote = $this->paymentData->getByOrder($order);

        /** @var Store $store */
        $store = $order->getStore();

        $state = Order::STATE_PROCESSING;
        $status = $this->paymentMenuConfig->getProcessedOrderStatus($store);

        if (0 >= $order->getGrandTotal()) {
            $state = Order::STATE_NEW;
            $status = $stateObject->getStatus();
        }

        $stateObject->setState($state);
        $stateObject->setStatus($status);

        $stateObject->setIsNotified(false);

        $transactionId = $paymentQuote->getPaymentOrderId();
        $payment->setBaseAmountAuthorized($order->getBaseTotalDue());
        $payment->setAmountAuthorized($order->getTotalDue());
        $payment->setTransactionId($transactionId)->setIsTransactionClosed(0);
        $payment->addTransaction(self::TYPE_AUTH);

        return null;
    }
}
