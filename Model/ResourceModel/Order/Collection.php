<?php

namespace SwedbankPay\Checkout\Model\ResourceModel\Order;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 *
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class Collection extends AbstractCollection
{
    // phpcs:disable
    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'swedbank_pay_orders_collection';
    protected $_eventObject = 'orders_collection';
    // phpcs:enable

    /**
     * Define resource model
     *
     * @return void
     *
     *  phpcs:disable
     */
    protected function _construct()
    {
        // phpcs:enable
        $this->_init('SwedbankPay\Menu\Model\Order', 'SwedbankPay\Menu\Model\ResourceModel\Order');
    }
}
