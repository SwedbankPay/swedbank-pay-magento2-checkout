define([
    'jquery',
    'mage/utils/wrapper',
    'SwedbankPay_Checkout/js/action/trigger-shipping-information-validation'
], function ($, wrapper, triggerShippingInformationValidation) {
    'use strict';

    return function (placeOrderAction) {
        var isEnabled = window.checkoutConfig.SwedbankPay_Checkout.isEnabled;

        if(!isEnabled){ return placeOrderAction; }

        return wrapper.wrap(placeOrderAction, function(placeOrderAction, data, message){

            var dfd = $.Deferred();

            triggerShippingInformationValidation.trigger(function (validation) {
                if (validation.success) {
                    placeOrderAction(data, message).done(function(){
                        dfd.resolve();
                    });
                } else {
                    dfd.reject();
                }
            });

            return dfd;
        });
    }
});