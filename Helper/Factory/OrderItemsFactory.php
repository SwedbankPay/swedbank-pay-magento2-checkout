<?php

namespace SwedbankPay\Checkout\Helper\Factory;

use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use SwedbankPay\Api\Service\Paymentorder\Resource\Collection\OrderItemsCollection;

class OrderItemsFactory
{
    /**
     * @var OrderItemFactory
     */
    protected $orderItemFactory;

    /**
     * OrderItemsFactory constructor.
     * @param OrderItemFactory $orderItemFactory
     */
    public function __construct(OrderItemFactory $orderItemFactory)
    {
        $this->orderItemFactory = $orderItemFactory;
    }

    /**
     * @param Quote $quote
     * @return OrderItemsCollection
     */
    public function create(Quote $quote)
    {
        $orderItems = new OrderItemsCollection();

        foreach ($quote->getAllVisibleItems() as $quoteItem) {
            $orderItem = $this->orderItemFactory->createByQuoteItem($quoteItem);
            $orderItems->addItem($orderItem);
        }

        $orderItem = $this->orderItemFactory->createShippingByQuote($quote);
        $orderItems->addItem($orderItem);

        return $orderItems;
    }

    /**
     * @param Order $order
     * @return OrderItemsCollection
     */
    public function createByOrder(Order $order)
    {
        $orderItems = new OrderItemsCollection();

        foreach ($order->getAllVisibleItems() as $mageOrderItem) {
            $orderItem = $this->orderItemFactory->createByOrderItem($mageOrderItem);
            $orderItems->addItem($orderItem);
        }

        $orderItem = $this->orderItemFactory->createShippingByOrder($order);
        $orderItems->addItem($orderItem);

        return $orderItems;
    }
}
