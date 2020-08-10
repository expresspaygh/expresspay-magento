# expresspay-magento
expressPay payment plugin for Magento 2

## Installation

### 1.a) Using Composer
````
composer require expresspay/magento
````

### 1.b) Manual Installation
1. Create the folder "app/code" in the root of your Magento installation, if it doesn't already exist
2. Copy/upload the contents of this package into "app/code" folder of your Magento installation

### 2) Enable module
Run the following commands in the root of your Magento installation
````
bin/magento module:enable ExpressPay_Checkout
bin/magento setup:upgrade
````

You should now see ExpressPay under Sales->Configuration->Sales->Payment Methods. Provide your Merchant ID and API Key, and select between "Sandbox" and "Live" to start processing payments.