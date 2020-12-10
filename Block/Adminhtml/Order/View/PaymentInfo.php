<?php

namespace SwedbankPay\Checkout\Block\Adminhtml\Order\View;

use Exception;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use SwedbankPay\Api\Client\Exception as ClientException;
use SwedbankPay\Api\Service\Paymentorder\Request\GetCurrentPayment;
use SwedbankPay\Checkout\Api\OrderRepositoryInterface as PaymentOrderRepository;
use SwedbankPay\Core\Exception\ServiceException;
use SwedbankPay\Core\Model\Service;

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
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * PaymentInfo constructor.
     * @param Context $context
     * @param array $data
     * @param Service $service
     * @param PaymentOrderRepository $paymentOrderRepo
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        Context $context,
        Service $service,
        PaymentOrderRepository $paymentOrderRepo,
        OrderRepository $orderRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->service = $service;
        $this->paymentOrderRepo = $paymentOrderRepo;
        $this->orderRepository = $orderRepository;
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
        } catch (ClientException $e) {
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

    /**
     * @return string|null
     */
    public function getSwedbankPayTransactionNumber()
    {
        $orderId = $this->getRequest()->getParam('order_id');

        try {
            /** @var Order $order */
            $order = $this->orderRepository->get($orderId);
        } catch (Exception $e) {
            return null;
        }

        return $order->getData('swedbank_pay_transaction_number');
    }
}
