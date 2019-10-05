var config = {
    config: {
        mixins: {
            "Magento_Checkout/js/view/shipping": {
                "SwedbankPay_Checkout/js/view/shipping-extended": true
            },
            "Magento_Checkout/js/view/form/element/email": {
                "SwedbankPay_Checkout/js/view/email-extended": true
            },
            "Magento_Checkout/js/model/step-navigator": {
                "SwedbankPay_Checkout/js/model/step-navigator-mixin": true
            },
            "Magento_Checkout/js/action/place-order": {
                "SwedbankPay_Checkout/js/action/place-order-wrapper": true
            }
        }
    },
    map: {
        "*": {
            "checkinStyling": "SwedbankPay_Checkout/js/checkin-styling",
            "paymentMenuStyling": "SwedbankPay_Checkout/js/payment-menu-styling"
        }
    }
};