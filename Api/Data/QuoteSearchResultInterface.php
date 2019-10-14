<?php

namespace SwedbankPay\Checkout\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface QuoteSearchResultInterface extends SearchResultsInterface
{
    /**
     * @return QuoteInterface[]
     */
    public function getItems();

    /**
     * @param QuoteInterface[] $items
     * @return void
     */
    public function setItems(array $items);
}
