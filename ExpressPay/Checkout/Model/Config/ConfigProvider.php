<?php

namespace ExpressPay\Checkout\Model\Config;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\View\Asset\Repository as Asset;

class ConfigProvider implements ConfigProviderInterface {
    protected $asset;

    public function __construct(Asset $asset) {
        $this->asset = $asset;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig() {
        $config = [
            'payment' => [
                'expresspay_checkout' => [
                    'logoUrl' => $this->logoUrl(),
                ]
            ]
        ];
        return $config;
    }

    protected function logoUrl() {
        return $this->asset->getUrl('ExpressPay_Checkout::images/logo.png');
    }
}