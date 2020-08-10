<?php

namespace ExpressPay\Checkout\Model\Config\Source;

/**
 * Environment Model
 */
class Environment implements \Magento\Framework\Option\ArrayInterface
{

    public function toOptionArray()
    {
        return [
            'sandbox' => 'Sandbox',
            'live' => 'Live',
        ];
    }
}
