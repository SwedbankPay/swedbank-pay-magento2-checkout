<?php

namespace SwedbankPay\Checkout\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Event\Manager as EventManager;
use Magento\Framework\Stdlib\CookieManagerInterface;
use SwedbankPay\Checkout\Model\ConsumerSession;
use SwedbankPay\Core\Model\Service;
use SwedbankPay\Core\Logger\Logger;
use SwedbankPay\Checkout\Helper\Config as ConfigHelper;
use SwedbankPay\Checkout\Helper\Paymentorder as PaymentorderHelper;

class OnUpdated extends PaymentActionAbstract
{
    /**
     * @var Service $service
     */
    protected $service;

    /**
     * @var PaymentorderHelper $paymentorderHelper
     */
    protected $paymentorderHelper;

    /**
     * @var ConsumerSession $consumerSession
     */
    protected $consumerSession;

    /**
     * @var CookieManagerInterface $cookieManager
     */
    protected $cookieManager;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        EventManager $eventManager,
        ConfigHelper $configHelper,
        Logger $logger,
        Service $service,
        PaymentorderHelper $paymentorderHelper,
        ConsumerSession $consumerSession,
        CookieManagerInterface $cookieManager
    ) {
        parent::__construct($context, $resultJsonFactory, $eventManager, $configHelper, $logger);

        $this->service = $service;
        $this->paymentorderHelper = $paymentorderHelper;
        $this->consumerSession = $consumerSession;
        $this->cookieManager = $cookieManager;

        $this->setEventName('updated');
        $this->setEventMethod([$this, 'updatePaymentOrder']);
    }

    /**
     * @return array|bool|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|string
     * @throws \Exception
     */
    public function updatePaymentOrder()
    {
        $consumerProfileRef = $this->cookieManager->getCookie('consumerProfileRef');

        $paymentOrderObject = $this->paymentorderHelper->createPaymentorderObject($consumerProfileRef);
        $paymentOrderPurchase = $this->service->init('Paymentorder', 'purchase', $paymentOrderObject);

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $this->logger->debug(basename(__CLASS__) . ' triggered');

        /** @var \SwedbankPay\Api\Service\Data\ResponseInterface $response */
        $response = $paymentOrderPurchase->send();
        $responseData = $response->getResponseData();
        $href = $response->getOperationByRel('view-paymentorder', 'href');

        $this->logger->debug('Updating payment order with ID: ' . $responseData['payment_order']['id']);

        $this->paymentorderHelper->saveQuoteToDB($responseData);

        return $href;
    }
}
