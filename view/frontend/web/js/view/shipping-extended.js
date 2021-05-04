define([
    'jquery',
    'ko',
    'SwedbankPay_Checkout/js/action/trigger-shipping-information-validation',
    'SwedbankPay_Checkout/js/action/shipping-methods-view',
    'SwedbankPay_Checkout/js/action/email-observer',
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Checkout/js/action/set-shipping-information',
    'Magento_Checkout/js/action/get-payment-information',
    'Magento_Checkout/js/action/select-shipping-address',
    'Magento_Checkout/js/action/create-shipping-address',
    'rjsResolver',
    'uiRegistry',
    'mage/translate'
], function ($, ko, triggerShippingInformationValidation, shippingMethodsView, emailObserver, quote, customer, stepNavigator, setShippingInformationAction, getPaymentInformation, selectShippingAddress, createShippingAddress, resolver, registry, $t) {
    'use strict';

    var shippingMethodVisible = ko.observable(false);
    var isEnabled = window.checkoutConfig.SwedbankPay_Checkout.isEnabled;
    var isVisible = ko.observable(false);

    return function (Shipping) {
        var mixin = {
            shippingMethodVisible: shippingMethodVisible,
            isVisible: isVisible,
            initialize: function(){
                var self = this;
                this._super();

                shippingMethodsView.show = function() {
                    isVisible(true);
                    shippingMethodVisible(true);
                };

                triggerShippingInformationValidation.trigger = function (callback) {
                    callback({success: self.quickShippingInformationValidation(), message: 'validateShippingInformation was ran!'});
                };

                emailObserver.get = function(){
                    if(self.quickShippingInformationValidation()) {
                        shippingMethodVisible(true);
                    } else {
                        shippingMethodVisible(false);
                    }
                };

                stepNavigator.hideSection('payment');

                quote.shippingMethod.subscribe(function(method){
                    // Check is shipping method is set and valid
                    if(method && method.available && shippingMethodVisible()){
                        setShippingInformationAction().done(function() {
                            getPaymentInformation().done(function(){
                                stepNavigator.showSection('payment');
                            });
                        });
                    } else {
                        stepNavigator.hideSection('payment');
                    }
                });

                resolver(function(){
                    if(self.quickShippingInformationValidation()) {
                        shippingMethodVisible(true);
                    }
                });

                shippingMethodVisible.subscribe(function(value){
                    if(value && quote.shippingMethod() && quote.shippingMethod().available){
                        getPaymentInformation().done(function(){
                            stepNavigator.showSection('payment');
                        });
                    } else {
                        stepNavigator.hideSection('payment');
                    }
                });

                registry.async('checkoutProvider')(function (checkoutProvider) {
                    checkoutProvider.on('shippingAddress', function (shippingAddrsData) {
                        if(self.quickShippingInformationValidation()) {
                            var newShippingAddress = createShippingAddress(shippingAddrsData);
                            selectShippingAddress(newShippingAddress);
                            shippingMethodVisible(true);
                        } else {
                            shippingMethodVisible(false);
                        }
                    });
                });
            },
            quickShippingInformationValidation: function(){
                return isVisible();
            }
        };

        if(!isEnabled){ return Shipping; }

        return Shipping.extend(mixin);
    };
});