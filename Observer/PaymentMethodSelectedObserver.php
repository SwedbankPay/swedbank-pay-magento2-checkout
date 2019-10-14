<?php

namespace SwedbankPay\Checkout\Observer;

use Magento\Framework\Event\Manager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Payment\Model\Method\Adapter;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use SwedbankPay\Checkout\Helper\Config;

class PaymentMethodSelectedObserver implements ObserverInterface
{
    /**
     * @var Manager
     */
    protected $eventManager;

    /**
     * @var Config $config
     */
    protected $config;

    /**
     * PaymentMethodSelectedObserver constructor.
     * @param Manager $eventManager
     * @param Config $config
     */
    public function __construct(
        Manager $eventManager,
        Config $config
    ) {
        $this->eventManager = $eventManager;
        $this->config = $config;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if (!$this->config->isActive()) {
            return;
        }

        /** @var Adapter $methodAdapter */
        $methodAdapter = AbstractDataAssignObserver::METHOD_CODE;

        $methodCode = $observer->getData($methodAdapter->getCode());
        // $modelCode = $observer->getData(AbstractDataAssignObserver::MODEL_CODE);
        // $dataCode = $observer->getData(AbstractDataAssignObserver::DATA_CODE);

        if (strpos($methodCode, 'swedbank_pay') !== false) {
            $this->eventManager->dispatch(
                'swedbank_pay_checkout_payment_menu_selected',
                []
            );
        }
    }
}
