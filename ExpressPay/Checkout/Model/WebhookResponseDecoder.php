<?php

namespace ExpressPay\Checkout\Model;

class WebhookResponseDecoder {
    public function decode($data) {
        parse_str($data, $result);
        return $result;
    }
}
