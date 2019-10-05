<?php

namespace SwedbankPay\Checkout\Block\Adminhtml\Order\View;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use PayEx\Api\Client\Exception;
use PayEx\Api\Service\Paymentorder\Request\GetCurrentPayment;
use SwedbankPay\Core\Exception\ServiceException;
use SwedbankPay\Core\Model\Service;
use SwedbankPay\Checkout\Api\OrderRepositoryInterface as PaymentOrderRepository;

class PaymentInfo extends Template
{
    /**
     * @var Service
     */
    protected $service;

    /**
     * @var PaymentOrderRepository
     */
    protected $paymentOrderRepo;

    /**
     * PaymentInfo constructor.
     * @param Context $context
     * @param array $data
     * @param Service $service
     * @param PaymentOrderRepository $paymentOrderRepo
     */
    public function __construct(
        Context $context,
        /** @noinspection PhpOptionalBeforeRequiredParametersInspection */ array $data = [],
        /** phpcs:disable */Service $service,
        PaymentOrderRepository $paymentOrderRepo /** phpcs:enable */
    ) {
        parent::__construct($context, $data);
        $this->service = $service;
        $this->paymentOrderRepo = $paymentOrderRepo;
    }

    /**
     * @return array|null
     * @throws Exception
     * @throws ServiceException
     * @throws NoSuchEntityException
     */
    public function getCurrentPayment()
    {
        /** @var GetCurrentPayment $serviceRequest */
        $serviceRequest = $this->service->init('Paymentorder', 'GetCurrentPayment');
        $serviceRequest->setRequestEndpoint('/psp/paymentorders/' . $this->getCurrentPaymentId() . '/currentpayment');

        return $serviceRequest->send()->getResponseData();
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function getCurrentPaymentId()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        $paymentOrderData = $this->paymentOrderRepo->getByOrderId($orderId);

        return $paymentOrderData->getPaymentOrderId();
    }
}
