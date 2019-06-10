/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'uiComponent',
    'ko',
    'PayEx_Checkout/js/action/email-observer',
], function ($, Component, ko, emailObserver) {
    'use strict';

    var isEnabled = window.checkoutConfig.PayEx_Checkout.isEnabled;

    return function (Email) {
        var mixin = {
            initialize: function(){
                var self = this;
                self._super();

                self.email.subscribe(function(data){
                    emailObserver.get(data);
                });
            }
        };

        if(!isEnabled){ return Email; }

        return Email.extend(mixin);
    };
});
