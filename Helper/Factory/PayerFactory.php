<?php

namespace SwedbankPay\Checkout\Helper\Factory;

use Magento\Quote\Model\Quote;
use SwedbankPay\Api\Service\Paymentorder\Resource\PaymentorderPayer;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class PayerFactory
{
    /**
     * @var ShippingAddressFactory
     */
    protected $shippingAddressFactory;

    /**
     * PayerFactory constructor.
     * @param ShippingAddressFactory $shippingAddressFactory
     */
    public function __construct(ShippingAddressFactory $shippingAddressFactory)
    {
        $this->shippingAddressFactory = $shippingAddressFactory;
    }

    /**
     * @param Quote $quote
     * @return PaymentorderPayer
     */
    public function create(Quote $quote)
    {
        $shippingAddress = $this->shippingAddressFactory->create($quote);

        $payer = new PaymentorderPayer();
        $payer->setEmail($quote->getShippingAddress()->getEmail())
            ->setMsisdn($quote->getShippingAddress()->getTelephone())
            ->setShippingAddress($shippingAddress);

        return $payer;
    }
}
