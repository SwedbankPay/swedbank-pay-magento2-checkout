<?php

namespace SwedbankPay\Checkout\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Event\Manager as EventManager;

use SwedbankPay\Core\Logger\Logger;
use SwedbankPay\Checkout\Helper\Config as ConfigHelper;

class OnPaymentCanceled extends PaymentActionAbstract
{
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        EventManager $eventManager,
        ConfigHelper $configHelper,
        Logger $logger
    ) {
        parent::__construct($context, $resultJsonFactory, $eventManager, $configHelper, $logger);

        $this->setEventName('payment_canceled');
        $this->setEventArgs(['id', 'redirectUrl']);
        $this->setEventMethod([$this, 'logCancellation']);
    }

    public function logCancellation($paymentId, $redirectUrl)
    {
        $this->logger->debug(
            sprintf("SwedbankPay Payment Order %s was cancelled, redirecting to: %s", $paymentId, $redirectUrl)
        );
    }
}
