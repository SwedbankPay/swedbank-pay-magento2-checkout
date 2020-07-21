<?php


namespace SwedbankPay\Checkout\Helper\Factory;

use Magento\Quote\Model\Quote;
use SwedbankPay\Api\Service\Paymentorder\Resource\PaymentorderRiskIndicator;
use SwedbankPay\Checkout\PluginHook;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class RiskIndicatorFactory
{
    /**
     * @var PluginHook
     */
    protected $pluginHook;

    /**
     * RiskIndicatorFactory constructor.
     * @param PluginHook $pluginHook
     */
    public function __construct(PluginHook $pluginHook)
    {
        $this->pluginHook = $pluginHook;
    }

    /**
     * @param Quote $quote
     * @return PaymentorderRiskIndicator
     */
    public function create(Quote $quote)
    {
        $riskIndicator = new PaymentorderRiskIndicator();

        $deliveryEmailAddress = $this->pluginHook->getDeliveryEmailAddress($quote, []);
        $deliveryTimeFrameIndicator = $this->pluginHook->getDeliveryTimeFrameIndicator($quote, []);
        $preOrderDate = $this->pluginHook->getPreOrderDate($quote, []);
        $preOrderPurchaseIndicator = $this->pluginHook->getPreOrderPurchaseIndicator($quote, []);
        $shipIndicator = $this->pluginHook->getShipIndicator($quote, []);
        $isGiftCardPurchase = $this->pluginHook->isGiftCardPurchase($quote, []);
        $reOrderPurchaseIndicator = $this->pluginHook->getReOrderPurchaseIndicator($quote, []);

        if ($deliveryEmailAddress) {
            $riskIndicator->setDeliveryEmailAddress($deliveryEmailAddress);
        }

        if ($deliveryTimeFrameIndicator) {
            $riskIndicator->setDeliveryTimeFrameIndicator($deliveryTimeFrameIndicator);
        }

        if ($preOrderDate) {
            $riskIndicator->setPreOrderDate($preOrderDate);
        }

        if ($preOrderPurchaseIndicator) {
            $riskIndicator->setPreOrderPurchaseIndicator($preOrderPurchaseIndicator);
        }

        if ($shipIndicator) {
            $riskIndicator->setShipIndicator($shipIndicator);
        }

        if ($reOrderPurchaseIndicator) {
            $riskIndicator->setReOrderPurchaseIndicator($reOrderPurchaseIndicator);
        }

        $riskIndicator->setGiftCardPurchase($isGiftCardPurchase);

        return $riskIndicator;
    }
}
