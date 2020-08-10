<?php

namespace ExpressPay\Checkout\Model\Config\Source;

/**
 * Countries Model
 */
class Countries implements \Magento\Framework\Option\ArrayInterface {

    public function toOptionArray() {
        return [
            array('value' => 'GH', 'label'=>('Ghana')),
        ];
    }
}
