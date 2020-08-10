<?php

namespace ExpressPay\Checkout\Api;

interface ApiInterface {
    /**
     * Webhook via API
     * @param string $param
     * @return string
     */
    public function webhook();

    /**
     * Redirect to ExpressPay via API
     * @param string $param
     * @return string
     */
    public function redirect();

    /**
     * Process return url from ExpressPay via API
     * @param string $param
     * @return string
     */
    public function returnUrl();
}