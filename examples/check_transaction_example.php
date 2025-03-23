<?php

// Enable debugging
define('IMEPAY_DEBUG', true);

// Include autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use IMEPaySDK\IMEPayment;
use IMEPaySDK\Environment;
use IMEPaySDK\IMEPayException;

// Create a new IMEPayment instance
$payment = new IMEPayment([
    'environment' => Environment::TEST,
    'merchant_code' => 'TEST',
    'merchant_name' => 'TEST',
    'module' => 'TEST',
    'username' => 'TEST',
    'password' => 'TEST'
]);

try {
    // Get a token
    $token = $payment->getToken();

    if (!$token) {
        echo "Failed to get token. Error: ";
        print_r($payment->getResponseData());
        exit;
    }

    echo "Token received: " . $token . "\n";

    // Transaction ID to check
    $transactionId = 'TXN123456789'; // Your original transaction ID

    // Check the transaction status
    $response = $payment->checkTransactionStatus(
        $token,
        $transactionId
    );

    echo "Transaction status check response:\n";
    print_r($response);

    if (isset($response['success']) && $response['success']) {
        echo "Transaction status check successful!\n";
        echo "Status: " . ($response['Status'] ?? 'Unknown') . "\n";
    } else {
        echo "Failed to check transaction status. Error: ";
        print_r($response);
    }

} catch (IMEPayException $e) {
    echo "IME Pay Error: " . $e->getMessage() . "\n";
    echo "Error Data: ";
    print_r($e->getErrorData());
} catch (Exception $e) {
    echo "General Error: " . $e->getMessage() . "\n";
}
