define([
    'uiComponent',
    'jquery',
    'ko',
    'underscore',
    'mage/translate',
    'mage/storage',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Checkout/js/model/quote',
    'uiRegistry',
    'Magento_Checkout/js/model/new-customer-address',
    'Magento_Checkout/js/action/set-shipping-information',
    'SwedbankPay_Checkout/js/action/open-shipping-information',
    'SwedbankPay_Checkout/js/action/trigger-shipping-information-validation',
    'Magento_Checkout/js/model/address-converter',
    'Magento_Checkout/js/checkout-data',
    'checkinStyling',
    'mage/cookies'
], function (Component, $, ko, _, $t, storage, stepNavigator, quote, registry, newCustomerAddress, setShippingInformationAction, openShippingInformation, triggerShippingInformationValidation, addressConverter, checkoutData, checkinStyling) {
    'use strict';

    var SwedbankPay = window.swedbankPay,
        onConsumerIdentifiedDelay = ko.observable(false),
        isEnabled = ko.observable(false),
        isVisible = ko.observable(false),
        isShippingSectionVisible = ko.observable(false),
        isRequired = ko.observable(false),
        isCheckedIn = ko.observable(false);

    return Component.extend({
        config: {
            data: {
                element: 'checkin-widget',
                shippingDetails: ko.observable({}),
                billingDetails: ko.observable({})
            }
        },
        isEnabled: isEnabled,
        isVisible: isVisible,
        isShippingSectionVisible: isShippingSectionVisible,
        isRequired: isRequired,
        isCheckedIn: isCheckedIn,
        initialize: function(config){
            var self = this;

            self._super();

            // Check if data comes from widget config or checkout config
            config.isCheckout = !config.hasOwnProperty('data');

            if(config.isCheckout) {
                Object.assign(self.config.data, window.checkoutConfig.SwedbankPay_Checkout);
                self.config.isCheckout = config.isCheckout;

                self.isEnabled((this.config.data.isEnabled == true));
                self.isRequired((this.config.data.isRequired == true));

                if(self.isEnabled) {
                    stepNavigator.steps.subscribe(function (section) {
                        stepNavigator.hideSection('shipping');
                        isShippingSectionVisible(false);
                    });
                }

            } else {
                Object.assign(self.config, config);
            }

            openShippingInformation.open = function(){
                self.proceedAsGuest();
            };

            if(self.isEnabled) {
                // Make request to get consumer info if user logged in through checkin in current session
                self.checkIsCheckedIn();
            }
        },
        checkIsCheckedIn: function(){
            var self = this;

            storage.post(
                this.config.data.OnConsumerReidentifiedUrl,
                "",
                true
            ).done(function(response){
                self.isVisible((self.config.data.isEnabled));

                // If previously logged in, autofill shipping and billing and don't load checkin window
                if(response.shipping_details || response.billing_details) {
                    if (self.config.isCheckout) {
                        setTimeout(function () {
                            self.autofillShippingDetails(response.shipping_details);
                            self.autofillBillingDetails(response.billing_details);
                            self.onCheckinValidation();
                        }, 500)
                    }
                } else {
                    if(!self.config.isCheckout) {
                        self.swedbankPaySetupHostedView();
                    }
                }

            }).fail(function(message){
                console.log(message);

                self.isVisible((self.config.data.isEnabled));
                self.swedbankPaySetupHostedView();
            });
        },
        onCheckinValidation: function(){
            var self = this,
                consumerProfileRef = $.cookie('consumerProfileRef');

            if(consumerProfileRef){
                self.isCheckedIn(true);

                triggerShippingInformationValidation.trigger(function(result) {
                    if(!result.success) {
                        self.proceedAsGuest();
                    }
                });
            }
        },
        proceedAsGuest: function(element, event){
            stepNavigator.showSection('shipping');
            this.isShippingSectionVisible(true);
        },
        swedbankPaySetupHostedView: function(){
            payex.hostedView.consumer({
                container: this.config.data.element,
                onConsumerIdentified: this.onConsumerIdentified.bind(this),
                onShippingDetailsAvailable: this.onShippingDetailsAvailable.bind(this),
                onBillingDetailsAvailable: this.onBillingDetailsAvailable.bind(this),
                style: checkinStyling
            }).open();
        },
        onConsumerIdentified: function(data){
            let self = this;

            if (data.hasOwnProperty('consumerProfileRef')) {
                $.cookie('consumerProfileRef', data.consumerProfileRef);
            }

            storage.post(
                this.config.data.OnConsumerIdentifiedUrl,
                JSON.stringify(data),
                true
            ).done(function(response){
                onConsumerIdentifiedDelay(true);
                self.isCheckedIn(true);
            }).fail(function(message){
                console.error(message);
            });
        },
        onShippingDetailsAvailable: function(data){
            let self = this;

            if (data.hasOwnProperty('url')) {
                $.cookie('shippingDetailsAvailableUrl', data.url);
            }

            onConsumerIdentifiedDelay.subscribe(function(value) {
                if(value) {
                    storage.post(
                        self.config.data.OnShippingDetailsAvailableUrl,
                        JSON.stringify(data),
                        true
                    ).done(function (response) {
                        if (self.config.isCheckout) {
                            self.autofillShippingDetails(response.data);
                        }
                    }).fail(function (message) {
                        console.error(message);
                    });
                }
            });

        },
        onBillingDetailsAvailable: function(data){
            let self = this;

            if (data.hasOwnProperty('url')) {
                $.cookie('billingDetailsAvailableUrl', data.url);
            }

            onConsumerIdentifiedDelay.subscribe(function(value) {
                if (value) {
                    storage.post(
                        self.config.data.OnBillingDetailsAvailableUrl,
                        JSON.stringify(data),
                        true
                    ).done(function (response) {
                        if (self.config.isCheckout) {
                            self.autofillBillingDetails(response.data);
                        }
                    }).fail(function (message) {
                        console.error(message);
                    });
                }
            });

        },
        separateAddressee: function(addressee) {
            var lastSpaceIndex = addressee.lastIndexOf(' ');

            return {
                firstname: addressee.substring(0, lastSpaceIndex),
                middlename: '',
                lastname: addressee.substring(lastSpaceIndex + 1)
            }
        },
        setEmailInputValue: function(email){
            var emailSelector = 'form[data-role=email-with-possible-login] input[type=email]';
            $(emailSelector).val(email).trigger('change');
        },
        getStreetAddressObject: function(streetAddressString){
            return {0: streetAddressString, 1: "", 2: "", 3: ""};
        },
        createAddressObject: function(swedbankPayAddress, addressKey) {

            if(!swedbankPayAddress[addressKey]){ return false; }

            var names = this.separateAddressee(swedbankPayAddress[addressKey].addressee);

            return {
                firstname: names.firstname,
                middlename: names.middlename,
                lastname: names.lastname,
                email: swedbankPayAddress.email,
                postcode: swedbankPayAddress[addressKey].zip_code,
                city: swedbankPayAddress[addressKey].city,
                street: this.getStreetAddressObject(swedbankPayAddress[addressKey].street_address),
                country_id: swedbankPayAddress[addressKey].country_code,
                company: '',
                canUseForBilling: ko.observable(true),
                telephone: swedbankPayAddress.msisdn
            }
        },
        autofillShippingDetails: function(swedbankPayShippingInformation){
            if(!swedbankPayShippingInformation) {
                return;
            }

            // Create new address object
            var shippingAddress = this.createAddressObject(swedbankPayShippingInformation, 'shipping_address');

            if(shippingAddress) {
                this.config.data.shippingDetails(shippingAddress);

                // Create new address object
                var address = newCustomerAddress(shippingAddress);
                address.street = this.getStreetAddressObject(swedbankPayShippingInformation['shipping_address'].street_address);

                // Set email input to logged in customer email
                this.setEmailInputValue(shippingAddress.email);

                // Update quote with logged in address object
                quote.shippingAddress(address);

                // Prepare address for auto-filling
                var addressFormPrepared = addressConverter.quoteAddressToFormAddressData(address);

                // Autofill fields
                registry.async('checkoutProvider')(function (checkoutProvider) {
                    checkoutProvider.set('shippingAddress', addressFormPrepared);
                });
            }
        },
        autofillBillingDetails: function(swedbankPayBillingInformation){
            if(!swedbankPayBillingInformation) {
                return;
            }

            // Create new address object
            var billingAddress = this.createAddressObject(swedbankPayBillingInformation, 'billing_address');

            if(billingAddress) {
                this.config.data.billingDetails(billingAddress);

                // Create new address object
                var address = newCustomerAddress(billingAddress);

                // Update quote with logged in address object
                quote.billingAddress(address);
            }
        }
    });
});
