<?php

namespace ExpressPay\Checkout\Model;

/**
 * Pay In Store payment method model
 */
class Checkout extends \Magento\Payment\Model\Method\AbstractMethod {
    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'expresspay_checkout';

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = true;
}
