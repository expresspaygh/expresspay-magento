define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push({
            type: 'expresspay_checkout',
            component: 'ExpressPay_Checkout/js/view/payment/method-renderer/expresspay-checkout-method'
        });
        /** Add view logic here if needed */
        return Component.extend({});
    }
);