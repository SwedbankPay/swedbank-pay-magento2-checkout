<?php

namespace SwedbankPay\Checkout\Model\Checkout;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\UrlInterface;
use SwedbankPay\Checkout\Helper\Config;

/**
 * Class AdditionalConfigVars
 */
class AdditionalConfigVars implements ConfigProviderInterface
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var Resolver
     */
    protected $locale;

    /**
     * AdditionalConfigVars constructor.
     *
     * @param Config $config
     * @param Resolver $locale
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        Config $config,
        Resolver $locale,
        UrlInterface $urlBuilder
    ) {
        $this->config = $config;
        $this->locale = $locale;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return [
            'SwedbankPay_Checkout' => [
                'isEnabled' =>  $this->config->isActive(),
                'isRequired' => $this->config->getValue('required') ? true : false,
                'culture' => str_replace('_', '-', $this->locale->getLocale()),
                'OnConsumerIdentifiedUrl' => $this->urlBuilder->getUrl(
                    'SwedbankPayCheckout/Index/OnConsumerIdentified'
                ),
                'OnConsumerReidentifiedUrl' => $this->urlBuilder->getUrl(
                    'SwedbankPayCheckout/Index/OnConsumerReidentified'
                ),
                'OnBillingDetailsAvailableUrl' => $this->urlBuilder->getUrl(
                    'SwedbankPayCheckout/Index/OnBillingDetailsAvailable'
                ),
                'OnShippingDetailsAvailableUrl' => $this->urlBuilder->getUrl(
                    'SwedbankPayCheckout/Index/OnShippingDetailsAvailable'
                ),
                'onPaymentCancel' => $this->urlBuilder->getUrl(
                    'SwedbankPayCheckout/Index/OnPaymentCancel'
                ),
                'onPaymentCapture' => $this->urlBuilder->getUrl(
                    'SwedbankPayCheckout/Index/OnPaymentCapture'
                ),
                'onPaymentCompleted' => $this->urlBuilder->getUrl(
                    'SwedbankPayCheckout/Index/OnPaymentCompleted'
                ),
                'onPaymentCreated' => $this->urlBuilder->getUrl(
                    'SwedbankPayCheckout/Index/OnPaymentCreated'
                ),
                'onPaymentError' => $this->urlBuilder->getUrl(
                    'SwedbankPayCheckout/Index/OnPaymentError'
                ),
                'onPaymentFailed' => $this->urlBuilder->getUrl(
                    'SwedbankPayCheckout/Index/OnPaymentFailed'
                ),
                'onPaymentMenuInstrumentSelected' => $this->urlBuilder->getUrl(
                    'SwedbankPayCheckout/Index/OnPaymentMenuInstrumentSelected'
                ),
                'onPaymentReversal' => $this->urlBuilder->getUrl(
                    'SwedbankPayCheckout/Index/OnPaymentReversal'
                ),
                'onPaymentToS' => $this->urlBuilder->getUrl(
                    'SwedbankPayCheckout/Index/OnPaymentToS'
                ),
                'onUpdated' => $this->urlBuilder->getUrl(
                    'SwedbankPayCheckout/Index/OnUpdated'
                )
            ]
        ];
    }
}
