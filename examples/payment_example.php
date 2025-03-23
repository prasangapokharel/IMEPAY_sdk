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
    'password' => 'TEST',
    'reference_value' => 'Reference Value',
    'amount' => '2000.00',
    'transaction_recording_url' => 'https://yourwebsite.com/ime/callback'
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

    // Generate a unique transaction ID
    $transactionId = 'TXN' . time();

    // Initiate payment
    $response = $payment->initiatePayment(
        $token,
        $transactionId,
        'Product Name',
        'https://yourwebsite.com/product'
    );

    echo "Payment initiation response:\n";
    print_r($response);

    // In a real application, you would redirect the user to the payment URL
    if (isset($response['PaymentUrl'])) {
        echo "Redirect user to: " . $response['PaymentUrl'] . "\n";
        
        // Uncomment this line to actually redirect in a web application
        // header('Location: ' . $response['PaymentUrl']);
        // exit;
    } else {
        echo "Failed to get payment URL. Error: ";
        print_r($response);
    }

    // For demonstration purposes, let's check the transaction status
    // In a real application, this would be done after the user completes the payment
    echo "\nChecking transaction status (for demonstration only):\n";
    $statusResponse = $payment->checkTransactionStatus($token, $transactionId);
    print_r($statusResponse);

} catch (IMEPayException $e) {
    echo "IME Pay Error: " . $e->getMessage() . "\n";
    echo "Error Data: ";
    print_r($e->getErrorData());
} catch (Exception $e) {
    echo "General Error: " . $e->getMessage() . "\n";
}
