<?php

namespace SwedbankPay\Checkout\Helper\Factory;

use Magento\Quote\Model\Quote;
use SwedbankPay\Api\Service\Consumer\Resource\ConsumerAddress;

class ShippingAddressFactory
{
    /**
     * @param Quote $quote
     * @return ConsumerAddress
     */
    public function create(Quote $quote)
    {
        $shippingAddress = new ConsumerAddress();
        $quoteAddress = $quote->getShippingAddress();

        if ($quoteAddress->getName()) {
            $shippingAddress->setAddressee($quoteAddress->getName());
        }

        if ($quoteAddress->getStreetFull()) {
            $shippingAddress->setStreetAddress($quoteAddress->getStreetFull());
        }

        if ($quoteAddress->getCity()) {
            $shippingAddress->setCity($quoteAddress->getCity());
        }

        if ($quoteAddress->getPostcode()) {
            $shippingAddress->setZipCode($quoteAddress->getPostcode());
        }

        if ($quoteAddress->getCountryModel() && $quoteAddress->getCountryModel()->getCountryId()) {
            $shippingAddress->setCountryCode($quoteAddress->getCountryModel()->getCountryId());
        }

        return $shippingAddress;
    }
}
