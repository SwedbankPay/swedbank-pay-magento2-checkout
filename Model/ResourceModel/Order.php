<?php

namespace SwedbankPay\Checkout\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;

/**
 * Class Order
 *
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class Order extends AbstractDb
{
    const MAIN_TABLE = 'swedbank_pay_orders';
    const ID_FIELD_NAME = 'id';

    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
    }

    // phpcs:disable
    protected function _construct()
    {
        // phpcs:enable
        $this->_init(self::MAIN_TABLE, self::ID_FIELD_NAME);
    }
}
