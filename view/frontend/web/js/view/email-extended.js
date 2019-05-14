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

    return function (Shipping) {
        return Shipping.extend({
            initialize: function(){
                var self = this;
                self._super();

                self.email.subscribe(function(data){
                    emailObserver.get(data);
                });
            }
        });
    };
});
