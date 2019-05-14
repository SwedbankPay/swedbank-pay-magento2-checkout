define([
    'jquery',
    'mage/utils/wrapper',
    'PayEx_Checkout/js/action/trigger-shipping-information-validation'
], function ($, wrapper, triggerShippingInformationValidation) {
    'use strict';

    return function (placeOrderAction) {
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