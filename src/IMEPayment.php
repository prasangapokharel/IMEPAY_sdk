<?php

namespace IMEPaySDK;

/**
 * IMEPayment class for handling IME Pay payment gateway integration
 */
class IMEPayment
{
    /**
     * @var string The merchant code provided by IME Pay
     */
    private $merchantCode;
    
    /**
     * @var string The merchant name
     */
    private $merchantName;
    
    /**
     * @var string The module name
     */
    private $module;
    
    /**
     * @var string The username for authentication
     */
    private $username;
    
    /**
     * @var string The password for authentication
     */
    private $password;
    
    /**
     * @var string The reference value for the transaction
     */
    private $referenceValue;
    
    /**
     * @var string The transaction amount
     */
    private $amount;
    
    /**
     * @var string The URL for transaction recording
     */
    private $transactionRecordingUrl;
    
    /**
     * @var string The environment (LIVE or TEST)
     */
    private $environment;
    
    /**
     * @var string The base URL for API requests
     */
    private $baseUrl;
    
    /**
     * @var array Response data from the last API call
     */
    private $responseData;

    /**
     * Constructor
     * 
     * @param array $config Configuration parameters
     */
    public function __construct(array $config = [])
    {
        // Set default environment to TEST
        $this->environment = $config['environment'] ?? Environment::TEST;
        
        // Set the base URL based on environment
        $this->setBaseUrlFromEnvironment();
        
        // Set configuration parameters
        $this->merchantCode = $config['merchant_code'] ?? '';
        $this->merchantName = $config['merchant_name'] ?? '';
        $this->module = $config['module'] ?? '';
        $this->username = $config['username'] ?? '';
        $this->password = $config['password'] ?? '';
        $this->referenceValue = $config['reference_value'] ?? '';
        $this->amount = $config['amount'] ?? '';
        $this->transactionRecordingUrl = $config['transaction_recording_url'] ?? '';
    }

    /**
     * Set the base URL based on the environment
     */
    private function setBaseUrlFromEnvironment()
    {
        $this->baseUrl = $this->environment === Environment::LIVE 
            ? 'https://payment.imepay.com.np:7979/api/Web' 
            : 'https://testpayment.imepay.com.np:7979/api/Web';
    }

    /**
     * Get token for IME Pay transaction
     * 
     * @return string|null The token or null on failure
     */
    public function getToken()
    {
        $url = $this->baseUrl . '/GetToken';
        
        $data = [
            'MerchantCode' => $this->merchantCode,
            'MerchantName' => $this->merchantName,
            'Module' => $this->module,
            'Username' => $this->username,
            'Password' => $this->password
        ];
        
        $response = $this->makeRequest($url, $data);
        
        if (isset($response['TokenId'])) {
            return $response['TokenId'];
        }
        
        return null;
    }
    
    /**
     * Initiate payment
     * 
     * @param string $token The token received from getToken()
     * @param string $transactionId A unique transaction ID
     * @param string $productName The name of the product being purchased
     * @param string $productUrl Optional product URL
     * @return array The payment response
     */
    public function initiatePayment($token, $transactionId, $productName, $productUrl = '')
    {
        $url = $this->baseUrl . '/Initiate';
        
        $data = [
            'TokenId' => $token,
            'MerchantCode' => $this->merchantCode,
            'RefId' => $transactionId,
            'Amount' => $this->amount,
            'ReferenceValue' => $this->referenceValue,
            'ProductName' => $productName,
            'ProductUrl' => $productUrl,
            'TransactionRecordingUrl' => $this->transactionRecordingUrl
        ];
        
        return $this->makeRequest($url, $data);
    }
    
    /**
     * Verify payment
     * 
     * @param string $token The token received from getToken()
     * @param string $transactionId The transaction ID used in initiatePayment()
     * @param string $refId The reference ID received from IME Pay
     * @return array The verification response
     */
    public function verifyPayment($token, $transactionId, $refId)
    {
        $url = $this->baseUrl . '/Verify';
        
        $data = [
            'TokenId' => $token,
            'MerchantCode' => $this->merchantCode,
            'RefId' => $transactionId,
            'TransactionId' => $refId
        ];
        
        return $this->makeRequest($url, $data);
    }
    
    /**
     * Check transaction status
     * 
     * @param string $token The token received from getToken()
     * @param string $transactionId The transaction ID used in initiatePayment()
     * @return array The transaction status response
     */
    public function checkTransactionStatus($token, $transactionId)
    {
        $url = $this->baseUrl . '/CheckTransaction';
        
        $data = [
            'TokenId' => $token,
            'MerchantCode' => $this->merchantCode,
            'RefId' => $transactionId
        ];
        
        return $this->makeRequest($url, $data);
    }
    
    /**
     * Cancel transaction
     * 
     * @param string $token The token received from getToken()
     * @param string $transactionId The transaction ID used in initiatePayment()
     * @param string $refId The reference ID received from IME Pay
     * @param string $reason The reason for cancellation
     * @return array The cancellation response
     */
    public function cancelTransaction($token, $transactionId, $refId, $reason = 'Cancelled by merchant')
    {
        $url = $this->baseUrl . '/Cancel';
        
        $data = [
            'TokenId' => $token,
            'MerchantCode' => $this->merchantCode,
            'RefId' => $transactionId,
            'TransactionId' => $refId,
            'Reason' => $reason
        ];
        
        return $this->makeRequest($url, $data);
    }
    
    /**
     * Make an HTTP request to the IME Pay API
     * 
     * @param string $url The API endpoint URL
     * @param array $data The data to send
     * @return array The response data
     */
    private function makeRequest($url, $data)
    {
        // Log the request for debugging
        $this->logRequest($url, $data);
        
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        // Log the response for debugging
        $this->logResponse($httpCode, $response, $error);
        
        if ($error) {
            $this->responseData = [
                'success' => false,
                'message' => 'cURL Error: ' . $error,
                'http_code' => $httpCode
            ];
            
            return $this->responseData;
        }
        
        $this->responseData = json_decode($response, true);
        
        if (!$this->responseData) {
            $this->responseData = [
                'success' => false,
                'message' => 'Invalid response from IME Pay API',
                'raw_response' => $response,
                'http_code' => $httpCode
            ];
        } else {
            // Add success flag based on response
            $this->responseData['success'] = $this->isSuccessResponse($this->responseData);
        }
        
        return $this->responseData;
    }
    
    /**
     * Determine if the response indicates success
     * 
     * @param array $response The response data
     * @return bool True if successful, false otherwise
     */
    private function isSuccessResponse($response)
    {
        // Check for common success indicators in IME Pay responses
        if (isset($response['ResponseCode']) && $response['ResponseCode'] === '0') {
            return true;
        }
        
        if (isset($response['Status']) && $response['Status'] === 'SUCCESS') {
            return true;
        }
        
        if (isset($response['TokenId']) && !empty($response['TokenId'])) {
            return true;
        }
        
        if (isset($response['PaymentUrl']) && !empty($response['PaymentUrl'])) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Log API request for debugging
     * 
     * @param string $url The API endpoint URL
     * @param array $data The request data
     */
    private function logRequest($url, $data)
    {
        if (defined('IMEPAY_DEBUG') && IMEPAY_DEBUG) {
            $logData = [
                'timestamp' => date('Y-m-d H:i:s'),
                'url' => $url,
                'data' => $data
            ];
            
            error_log('IME Pay API Request: ' . json_encode($logData, JSON_PRETTY_PRINT));
        }
    }
    
    /**
     * Log API response for debugging
     * 
     * @param int $httpCode The HTTP status code
     * @param string $response The raw response
     * @param string $error Any cURL error
     */
    private function logResponse($httpCode, $response, $error)
    {
        if (defined('IMEPAY_DEBUG') && IMEPAY_DEBUG) {
            $logData = [
                'timestamp' => date('Y-m-d H:i:s'),
                'http_code' => $httpCode,
                'response' => $response ? json_decode($response, true) : null,
                'error' => $error
            ];
            
            error_log('IME Pay API Response: ' . json_encode($logData, JSON_PRETTY_PRINT));
        }
    }
    
    /**
     * Get the last response data
     * 
     * @return array The response data
     */
    public function getResponseData()
    {
        return $this->responseData;
    }
    
    /**
     * Set the merchant code
     * 
     * @param string $merchantCode The merchant code
     * @return $this
     */
    public function setMerchantCode($merchantCode)
    {
        $this->merchantCode = $merchantCode;
        return $this;
    }
    
    /**
     * Set the merchant name
     * 
     * @param string $merchantName The merchant name
     * @return $this
     */
    public function setMerchantName($merchantName)
    {
        $this->merchantName = $merchantName;
        return $this;
    }
    
    /**
     * Set the module
     * 
     * @param string $module The module
     * @return $this
     */
    public function setModule($module)
    {
        $this->module = $module;
        return $this;
    }
    
    /**
     * Set the username
     * 
     * @param string $username The username
     * @return $this
     */
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }
    
    /**
     * Set the password
     * 
     * @param string $password The password
     * @return $this
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }
    
    /**
     * Set the reference value
     * 
     * @param string $referenceValue The reference value
     * @return $this
     */
    public function setReferenceValue($referenceValue)
    {
        $this->referenceValue = $referenceValue;
        return $this;
    }
    
    /**
     * Set the amount
     * 
     * @param string $amount The amount
     * @return $this
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }
    
    /**
     * Set the transaction recording URL
     * 
     * @param string $url The transaction recording URL
     * @return $this
     */
    public function setTransactionRecordingUrl($url)
    {
        $this->transactionRecordingUrl = $url;
        return $this;
    }
    
    /**
     * Set the environment
     * 
     * @param string $environment The environment (LIVE or TEST)
     * @return $this
     */
    public function setEnvironment($environment)
    {
        $this->environment = $environment;
        
        // Update the base URL based on the new environment
        $this->setBaseUrlFromEnvironment();
            
        return $this;
    }
}
