<?php

namespace SwedbankPay\Checkout\Block\Adminhtml\Order\View;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use SwedbankPay\Api\Client\Exception;
use SwedbankPay\Api\Service\Paymentorder\Request\GetCurrentPayment;
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
     */
    public function getCurrentPayment()
    {
        if (!$this->getCurrentPaymentId()) {
            return null;
        }

        try {
            /** @var GetCurrentPayment $serviceRequest */
            $serviceRequest = $this->service->init('Paymentorder', 'GetCurrentPayment');
        } catch (ServiceException $e) {
            return null;
        }

        $serviceRequest->setRequestEndpoint('/psp/paymentorders/' . $this->getCurrentPaymentId() . '/currentpayment');

        try {
            return $serviceRequest->send()->getResponseData();
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @return string|null
     */
    public function getCurrentPaymentId()
    {
        $orderId = $this->getRequest()->getParam('order_id');

        try {
            $paymentOrderData = $this->paymentOrderRepo->getByOrderId($orderId);
        } catch (NoSuchEntityException $e) {
            return null;
        }

        return $paymentOrderData->getPaymentOrderId();
    }

    /**
     * @return string|null
     */
    public function getCurrentPaymentInstrument()
    {
        $currentPayment = $this->getCurrentPayment();

        if ($currentPayment && array_key_exists('menu_element_name', $currentPayment)) {
            return $currentPayment['menu_element_name'];
        }

        return null;
    }
}
