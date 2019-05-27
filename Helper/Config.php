<?php

namespace PayEx\Checkout\Helper;

use PayEx\Core\Helper\Config as CoreConfig;

class Config extends CoreConfig
{
    const XML_CONFIG_GROUP = 'checkout';

    protected $moduleDependencies = [
        'PayEx_Client',
        'PayEx_Checkin',
        'PayEx_PaymentMenu'
    ];
}
