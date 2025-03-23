<?php

namespace IMEPaySDK;

use Exception;

/**
 * IMEPayException class for handling IME Pay specific exceptions
 */
class IMEPayException extends Exception
{
    /**
     * @var array Additional error data
     */
    protected $errorData;
    
    /**
     * Constructor
     * 
     * @param string $message The exception message
     * @param int $code The exception code
     * @param Exception $previous The previous exception
     * @param array $errorData Additional error data
     */
    public function __construct($message = "", $code = 0, Exception $previous = null, array $errorData = [])
    {
        parent::__construct($message, $code, $previous);
        $this->errorData = $errorData;
    }
    
    /**
     * Get the additional error data
     * 
     * @return array The error data
     */
    public function getErrorData()
    {
        return $this->errorData;
    }
}
