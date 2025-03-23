<?php

namespace IMEPaySDK;

/**
 * IMEPaymentCallback class for handling IME Pay payment callbacks
 */
class IMEPaymentCallback
{
    /**
     * @var string The merchant code provided by IME Pay
     */
    private $merchantCode;
    
    /**
     * @var string The merchant secret key for validating callbacks
     */
    private $merchantSecret;
    
    /**
     * @var array Raw callback data
     */
    private $rawData;
    
    /**
     * Constructor
     * 
     * @param string $merchantCode The merchant code
     * @param string $merchantSecret The merchant secret key
     */
    public function __construct($merchantCode, $merchantSecret)
    {
        $this->merchantCode = $merchantCode;
        $this->merchantSecret = $merchantSecret;
    }
    
    /**
     * Process the callback data from IME Pay
     * 
     * @param array $data The callback data
     * @return array The processed result
     */
    public function processCallback($data)
    {
        // Store raw data for logging
        $this->rawData = $data;
        
        // Log the callback data
        $this->logCallback($data);
        
        // Validate the callback data
        if (!$this->validateCallback($data)) {
            return [
                'success' => false,
                'message' => 'Invalid callback data',
                'data' => $data
            ];
        }
        
        // Process the callback data
        return [
            'success' => true,
            'transaction_id' => $data['TransactionId'] ?? null,
            'ref_id' => $data['RefId'] ?? null,
            'amount' => $data['Amount'] ?? null,
            'status' => $data['Status'] ?? null,
            'message' => $data['Message'] ?? null,
            'data' => $data
        ];
    }
    
    /**
     * Validate the callback data
     * 
     * @param array $data The callback data
     * @return bool True if valid, false otherwise
     */
    private function validateCallback($data)
    {
        // Check if required fields are present
        if (!isset($data['MerchantCode']) || !isset($data['TransactionId']) || !isset($data['RefId'])) {
            $this->logValidationError('Missing required fields');
            return false;
        }
        
        // Verify merchant code
        if ($data['MerchantCode'] !== $this->merchantCode) {
            $this->logValidationError('Invalid merchant code');
            return false;
        }
        
        // Verify signature if present
        if (isset($data['Signature'])) {
            $expectedSignature = $this->generateSignature($data);
            $isValid = $data['Signature'] === $expectedSignature;
            
            if (!$isValid) {
                $this->logValidationError('Invalid signature');
            }
            
            return $isValid;
        }
        
        // If no signature is present, check for other validation methods
        // For example, check if Status is a valid value
        if (isset($data['Status']) && !in_array($data['Status'], ['SUCCESS', 'FAILED', 'PENDING', 'CANCELLED'])) {
            $this->logValidationError('Invalid status value');
            return false;
        }
        
        return true;
    }
    
    /**
     * Generate a signature for validating callback data
     * 
     * @param array $data The callback data
     * @return string The generated signature
     */
    private function generateSignature($data)
    {
        // Remove the Signature field from the data
        $signatureData = $data;
        unset($signatureData['Signature']);
        
        // Sort the data by key
        ksort($signatureData);
        
        // Create a string from the data
        $signatureString = '';
        foreach ($signatureData as $key => $value) {
            $signatureString .= $key . '=' . $value . '&';
        }
        
        // Remove the trailing &
        $signatureString = rtrim($signatureString, '&');
        
        // Add the merchant secret
        $signatureString .= $this->merchantSecret;
        
        // Generate the signature
        return hash('sha256', $signatureString);
    }
    
    /**
     * Log callback data for debugging
     * 
     * @param array $data The callback data
     */
    private function logCallback($data)
    {
        if (defined('IMEPAY_DEBUG') && IMEPAY_DEBUG) {
            $logData = [
                'timestamp' => date('Y-m-d H:i:s'),
                'data' => $data
            ];
            
            error_log('IME Pay Callback: ' . json_encode($logData, JSON_PRETTY_PRINT));
        }
    }
    
    /**
     * Log validation error for debugging
     * 
     * @param string $error The validation error
     */
    private function logValidationError($error)
    {
        if (defined('IMEPAY_DEBUG') && IMEPAY_DEBUG) {
            $logData = [
                'timestamp' => date('Y-m-d H:i:s'),
                'error' => $error,
                'data' => $this->rawData
            ];
            
            error_log('IME Pay Callback Validation Error: ' . json_encode($logData, JSON_PRETTY_PRINT));
        }
    }
    
    /**
     * Get the raw callback data
     * 
     * @return array The raw callback data
     */
    public function getRawData()
    {
        return $this->rawData;
    }
}
