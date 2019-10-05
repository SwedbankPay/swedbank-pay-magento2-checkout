<?php

namespace SwedbankPay\Checkout\Helper;

use SwedbankPay\Checkout\Model\Ui\ConfigProvider;
use SwedbankPay\Core\Helper\Config as CoreConfig;

class Config extends CoreConfig
{
    const XML_CONFIG_GROUP = 'checkout';

    protected $moduleDependencies = [
        'SwedbankPay_Core'
    ];

    /**
     * Get the order status that should be set on orders that have been processed by SwedbankPay
     *
     * @param Store|int|string|null  $store
     *
     * @return string
     */
    public function getProcessedOrderStatus($store = null)
    {
        return $this->getPaymentValue('order_status', ConfigProvider::CODE, $store);
    }
}
