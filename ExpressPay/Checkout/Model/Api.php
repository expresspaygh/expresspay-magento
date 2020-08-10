<?php

namespace ExpressPay\Checkout\Model;

use Magento\Sales\Model\Order;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction;

use ExpressPay\Checkout\Api\ApiInterface;
use ExpressPay\Checkout\Model\Checkout;

class Api implements ApiInterface {
    private $checkoutSession;
    private $customerSession;
    private $orderFactory;
    private $scopeConfig;
    private $urlBuilder;
    private $redirect;
    private $response;
    private $message;
    private $invoiceService;
    private $dbTransaction;

    public function __construct(
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        OrderFactory $orderFactory,
        ScopeConfigInterface $scopeConfig,
        UrlInterface $urlBuilder,
        MessageManager $message,
        InvoiceService $invoiceService,
        Transaction $dbTransaction
    ) {   
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->orderFactory = $orderFactory;
        $this->scopeConfig = $scopeConfig;
        $this->urlBuilder = $urlBuilder;
        $this->message = $message;
        $this->invoiceService = $invoiceService;
        $this->dbTransaction = $dbTransaction;
    }

    public function webhook() {
        if (isset($_REQUEST['order-id']) && isset($_REQUEST['token'])) {
            $order_id = $_REQUEST['order-id'];
            $token = $_REQUEST['token'];
            $order = $this->orderFactory->create()->loadByIncrementId($order_id);
            if ($order->getState() == \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT) {
                if ($query = $this->queryByToken($token)) {
                    if ($query['result'] == 1) {
                        $order->addStatusHistoryComment('Payment received -> OrderId: ' . $order_id . ' Amount: ' . $query['amount'])->save();

                        if ($this->verifyPayment($query, $order, $_REQUEST)) {
                            $this->processOrder($order);
                        }
                    }
                    else {
                        $order->setStatus(Order::STATE_CANCELED)->setState(Order::STATE_CANCELED)->save();
                        $order->addStatusHistoryComment('Payment declined, OrderId: ' . $order_id, Order::STATE_PROCESSING )->setIsCustomerNotified(false)->save();
                    }
                }
            }
        }
        exit;
    }

    public function redirect() {
        $order_id = $this->checkoutSession->getData('last_real_order_id');
        $order = $this->orderFactory->create()->loadByIncrementId($order_id);

        $data = [];
        $merchantId = $this->getConfig('merchant_id');
        $apiKey = $this->getConfig('api_key');

        $fields = array(
            'redirect-url' => $this->urlBuilder->getUrl() . 'rest/V1/expresspay/return',
            'post-url' => $this->urlBuilder->getUrl() . 'rest/V1/expresspay/webhook',

            'firstname' => $order->getData('customer_firstname'),
            'lastname' => $order->getData('customer_lastname'),
            'email' => $order->getData('customer_email'),

            'order-id' => $order->getRealOrderId(),
            'amount' => $order->getGrandTotal(),
            'currency' => $order->getData('order_currency_code'),
        );

        if ($token = $this->getCheckoutToken($fields)) {
            $order->getPayment()->setAdditionalInformation('expresspay_token', $token['token'])->save();
            $order->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT)->save();

            $url = $this->buildExpressPayApiUrl('checkout.php', ['token' => $token['token']]);
            $this->redirectExternal($url);
        }

        $this->redirectInternal('checkout/cart', ['error' => 'Payment failed']);
    }

    public function returnUrl() {
        $destination = $this->loggedIn() ? 'sales/order/history' : 'checkout/cart';
        if (isset($_REQUEST['order-id']) && isset($_REQUEST['token'])) {
            if ($query = $this->queryByToken($_REQUEST['token'])) {
                if ($query['result'] == 1) {
                    $this->flushMessage('success', 'Payment successful. Your order will be updated shortly');
                }
                else {
                    $this->flushMessage('error', $query['result-text']);
                }
            }
        }
        $this->redirectInternal($destination);
    }

    protected function loggedIn() {
        return !empty($this->customerSession->getData('customer_id'));
    }

    protected function getConfig($field) {
        $prefix = 'payment/expresspay_checkout/';
        $key = $prefix . $field;
        return $this->scopeConfig->getValue($key, ScopeInterface::SCOPE_STORE);
    }

    protected function getCheckoutToken(Array $fields) {
        $request = $this->makeExpressPayApiRequest('submit.php', $fields);
        if (isset($request['token']) && $request['token']) {
            return $request;
        }
    }

    protected function buildExpressPayApiUrl($path = null, $params = []) {
        $mode = $this->getConfig('environment') == 'live' ? 'live' : 'sandbox';
        $url = 'https://' . ($mode == 'sandbox' ? 'sandbox.' : null) . 'expresspaygh.com';
        $url .= "/api/$path";
        if ($params)
            $url .= '?' . http_build_query($params, '', '&');

        return $url;
    }

    protected function makeExpressPayApiRequest($path, $params, $method = 'POST') {
        $method = strtoupper($method);
        $url = $this->buildExpressPayApiUrl($path, $method == 'GET' ? $params : []);
        $params['merchant-id'] = $this->getConfig('merchant_id');
        $params['api-key'] = $this->getConfig('api_key');

        $ch = curl_init();
    
        curl_setopt( $ch, CURLOPT_USERAGENT, 'ExpressPay Magento');
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_HEADER, false );
        // curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
        // curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );

        curl_setopt( $ch, CURLOPT_URL, $url );
        if ($method == 'POST') {
            curl_setopt( $ch, CURLOPT_POST, true );
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $params );
        }

        curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
    
        $response = curl_exec( $ch );
        curl_close( $ch );
        
        return json_decode($response, true);
    }

    protected function flushMessage($type, $message) {
        if ($type == 'error') {
            $this->message->addError($message);
        }
        elseif ($type == 'success') {
            $this->message->addSuccess($message);
        }
    }

    protected function redirectInternal($path, $options = []) {
        if (isset($options['error'])) {
            $this->flushMessage('error', $options['error']);
        }
        if (isset($options['message'])) {
            $this->flushMessage('success', $options['message']);
        }

        $url = $this->urlBuilder->getUrl($path);
        header('Location: ' . $url);
        exit;
    }

    protected function redirectExternal($url) {
        header('Location: ' . $url);
        exit;
    }

    protected function queryByToken($token) {
        if ($query = $this->makeExpressPayApiRequest('query.php', ['token' => $token])) {
            return $query;
        }
    }

    protected function processOrder($order) {
        try {
            if (!$order->hasInvoices()) {
                $invoice = $this->invoiceService->prepareInvoice($order);
                $invoice->register();
                $invoice->save();

                $transaction = $this->dbTransaction->addObject($invoice)->addObject($invoice->getOrder());
                $transaction->save();
            }

            $order->setState(Order::STATE_COMPLETE)->setStatus(Order::STATE_COMPLETE);
            $order->save();
        }
        catch (Exception $e) {}
    }

    protected function verifyPayment($payment, $order, $request) {
        if (
            $payment['order-id'] == $order->getRealOrderId() &&
            $payment['currency'] == $order->getData('order_currency_code') &&
            floatval($payment['amount']) >= floatval($order->getGrandTotal()) &&
            $request['merchant-id'] == $this->getConfig('merchant_id')
        ) {
            return true;
        }
    }
}
