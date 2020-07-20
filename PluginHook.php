<?php

namespace SwedbankPay\Checkout;

use Magento\Quote\Model\Quote;

/**
 * Class PluginHook
 *
 * Methods in this class are meant to be overridden by plugins
 * It seems like a good idea to pass a context array to these methods so that more stuff can
 * be added to them in the future without breaking external code.
 *
 * Any change to method signatures in this file, as well as moving it to another namespace, will break backwards
 * compatibility.
 *
 * @todo check that all these methods are properly implemented.
 * @api
 */
class PluginHook
{
    const ELECTRONIC_DELIVERY = '01';
    const SAME_DAY_SHIPPING = '02';
    const OVERNIGHT_SHIPPING = '03';
    const TWO_DAYS_OR_MORE_SHIPPING = '04';
    const MERCHANDISE_AVAILABILITY = '01';
    const FUTURE_AVAILABILITY = '02';
    const SHIP_TO_CARDHOLDERS_BILLING_ADDRESS = '01';
    const SHIP_TO_ANOTHER_VERIFIED_ADDRESS = '02';
    const SHIP_TO_ADDRESS_DIFFERENT_THAN_BILLING_ADDRESS = '03';
    const PICK_UP_AT_STORE = '04';
    const DIGITAL_GOODS = '05';
    const TRAVEL_AND_EVENT_TICKETS = '06';
    const OTHERS = '07';

    /**
     * @param Quote $quote
     * @param array $context
     * @return string|null
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getDeliveryEmailAddress(Quote $quote, array $context = [])
    {
        return $quote->getShippingAddress()->getEmail();
    }

    /**
     * @param Quote $quote
     * @param array $context
     * @return string|null
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getDeliveryTimeFrameIndicator(Quote $quote, array $context = [])
    {
        if ($quote->isVirtual()) {
            return self::ELECTRONIC_DELIVERY;
        }

        return self::TWO_DAYS_OR_MORE_SHIPPING;
    }

    /**
     * @param Quote $quote
     * @param array $context
     * @return string|null
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getPreOrderDate(Quote $quote, array $context = [])
    {
        return null;
    }

    /**
     * @param Quote $quote
     * @param array $context
     * @return string|null
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getPreOrderPurchaseIndicator(Quote $quote, array $context = [])
    {
        return null;
    }

    /**
     * @param Quote $quote
     * @param array $context
     * @return string|null
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getShipIndicator(Quote $quote, array $context = [])
    {
        return null;
    }

    /**
     * @param Quote $quote
     * @param array $context
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function isGiftCardPurchase(Quote $quote, array $context = [])
    {
        return false;
    }

    /**
     * @param Quote $quote
     * @param array $context
     * @return string|null
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getReOrderPurchaseIndicator(Quote $quote, array $context = [])
    {
        return null;
    }
}
