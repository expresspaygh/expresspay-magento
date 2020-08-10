/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/url',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/action/redirect-on-success',
    ],
    function ($, Component, placeOrderAction, selectPaymentMethodAction, customer, checkoutData, additionalValidators, url, quote, redirectOnSuccessAction) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'ExpressPay_Checkout/payment/checkout'
            },

            getCode: function() {
                return 'expresspay_checkout';
            },

            afterPlaceOrder: function () {
                redirectOnSuccessAction.redirectUrl = url.build('rest/V1/expresspay/redirect');
                this.redirectAfterPlaceOrder = true;
            },

            /** Returns send check to info */
            getMailingAddress: function() {
                return window.checkoutConfig.payment.checkmo.mailingAddress;
            },

            getLogoUrl: function() {
                return window.checkoutConfig.payment.expresspay_checkout.logoUrl;
            },    
        });
    }
);