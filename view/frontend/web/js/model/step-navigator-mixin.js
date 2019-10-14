define([
    'jquery',
    'ko'
], function (
    $,
    ko
) {
    'use strict';

    var isEnabled = window.checkoutConfig.SwedbankPay_Checkout.isEnabled;

    return function(stepNavigator) {
        var steps = stepNavigator.steps;

        var mixin = {
            registerStep: function (code, alias, title, isVisible, navigate, sortOrder) {
                var hash, active;

                if ($.inArray(code, this.validCodes) !== -1) {
                    throw new DOMException('Step code [' + code + '] already registered in step navigator');
                }

                if (alias != null) {
                    if ($.inArray(alias, this.validCodes) !== -1) {
                        throw new DOMException('Step code [' + alias + '] already registered in step navigator');
                    }
                    this.validCodes.push(alias);
                }

                this.validCodes.push(code);

                // Make all steps visible
                isVisible(true);

                stepNavigator.steps.push({
                    code: code,
                    alias: alias != null ? alias : code,
                    title: title,
                    isVisible: ko.observable(true),
                    toggleVisibility: isVisible,
                    navigate: navigate,
                    sortOrder: sortOrder
                });

                this.stepCodes.push(code);
            },
            hideSection: function(sectionName){
                var checkoutSteps = steps();

                checkoutSteps.forEach(function(step){
                    if(step.code == sectionName){
                        step.toggleVisibility(false);
                    }
                });
            },
            showSection: function(sectionName){
                var checkoutSteps = steps();

                checkoutSteps.forEach(function(step){
                    if(step.code == sectionName){
                        step.toggleVisibility(true);
                    }
                });
            },
            setHash: function (hash) {
                // Do nothing
            },
        };

        if(!isEnabled){ return stepNavigator; }

        return $.extend(stepNavigator, mixin);
    }
});