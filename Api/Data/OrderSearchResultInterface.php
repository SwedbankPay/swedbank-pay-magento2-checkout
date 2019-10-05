<?php

namespace SwedbankPay\Checkout\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface OrderSearchResultInterface extends SearchResultsInterface
{
    /**
     * @return OrderInterface[]
     */
    public function getItems();

    /**
     * @param OrderInterface[] $items
     * @return void
     */
    public function setItems(array $items);
}
