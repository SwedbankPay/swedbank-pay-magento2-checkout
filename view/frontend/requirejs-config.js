var config = {
    config: {
        mixins: {
            "Magento_Checkout/js/view/shipping": {
                "PayEx_Checkout/js/view/shipping-extended": true
            },
            "Magento_Checkout/js/view/form/element/email": {
                "PayEx_Checkout/js/view/email-extended": true
            },
            "Magento_Checkout/js/model/step-navigator": {
                "PayEx_Checkout/js/model/step-navigator-mixin": true
            },
            "Magento_Checkout/js/action/place-order": {
                "PayEx_Checkout/js/action/place-order-wrapper": true
            }
        }
    }
}