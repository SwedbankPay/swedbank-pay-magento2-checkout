<?php

namespace PayEx\Checkout\Model\Checkout;

use Magento\Checkout\Model\ConfigProviderInterface;
use PayEx\Checkout\Helper\Config as ConfigHelper;

class AdditionalConfigVars implements ConfigProviderInterface
{
    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * AdditionalConfigVars constructor.
     * @param ConfigHelper $configHelper
     */
    public function __construct(ConfigHelper $configHelper)
    {
        $this->configHelper = $configHelper;
    }

    public function getConfig()
    {
        return [
            'PayEx_Checkout' => [
                'isEnabled' =>  $this->configHelper->isActive()
            ]
        ];
    }
}
