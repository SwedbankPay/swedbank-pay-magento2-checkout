/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* @api */
define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
], function (Component, rendererList) {
    'use strict';

    rendererList.push(
        {
            type: 'swedbank_pay_checkout',
            component: 'SwedbankPay_Checkout/js/view/payment/method-renderer/payment-menu'
        }
    );

    /** Add view logic here if needed */
    return Component.extend({});
});
