<?php

// Enable debugging
define('IMEPAY_DEBUG', true);

// Include autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use IMEPaySDK\IMEPayment;
use IMEPaySDK\IMEPaymentCallback;
use IMEPaySDK\Environment;
use IMEPaySDK\IMEPayException;

// Get the callback data
$callbackData = $_POST;

// For testing purposes, you can use this sample data
if (empty($callbackData)) {
    $callbackData = [
        'MerchantCode' => 'TEST',
        'TransactionId' => 'IME123456789',
        'RefId' => 'TXN1234567890',
        'Amount' => '2000.00',
        'Status' => 'SUCCESS',
        'Message' => 'Payment successful',
        'Signature' => 'sample_signature' // This would be a real signature in production
    ];
}

try {
    // Create a new IMEPaymentCallback instance
    $callback = new IMEPaymentCallback('TEST', 'your_merchant_secret');

    // Process the callback
    $result = $callback->processCallback($callbackData);

    if ($result['success']) {
        echo "Payment successful!\n";
        echo "Transaction ID: " . $result['transaction_id'] . "\n";
        echo "Reference ID: " . $result['ref_id'] . "\n";
        echo "Amount: " . $result['amount'] . "\n";
        
        // Verify the payment with IME Pay
        $payment = new IMEPayment([
            'environment' => Environment::TEST,
            'merchant_code' => 'TEST',
            'merchant_name' => 'TEST',
            'module' => 'TEST',
            'username' => 'TEST',
            'password' => 'TEST'
        ]);
        
        $token = $payment->getToken();
        
        if ($token) {
            $verificationResponse = $payment->verifyPayment(
                $token,
                $result['ref_id'],
                $result['transaction_id']
            );
            
            echo "Payment verification response:\n";
            print_r($verificationResponse);
            
            if (isset($verificationResponse['success']) && $verificationResponse['success']) {
                echo "Payment verified successfully!\n";
                
                // Update your database with the payment information
                // ...
                
                // Return a success response to IME Pay
                header('Content-Type: application/json');
                echo json_encode(['status' => 'success']);
            } else {
                echo "Payment verification failed!\n";
                
                // Return an error response to IME Pay
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Payment verification failed']);
            }
        } else {
            echo "Failed to get token for verification!\n";
            
            // Return an error response to IME Pay
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Failed to get token for verification']);
        }
    } else {
        echo "Payment callback validation failed!\n";
        echo "Error: " . ($result['message'] ?? 'Unknown error') . "\n";
        
        // Return an error response to IME Pay
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $result['message'] ?? 'Validation failed']);
    }
} catch (IMEPayException $e) {
    echo "IME Pay Error: " . $e->getMessage() . "\n";
    
    // Return an error response to IME Pay
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} catch (Exception $e) {
    echo "General Error: " . $e->getMessage() . "\n";
    
    // Return an error response to IME Pay
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Internal server error']);
}
