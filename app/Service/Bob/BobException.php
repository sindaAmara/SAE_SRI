<?php

namespace Service\Bob;

use RuntimeException;

/**
 * BobException
 *
 * Business error raised by the Bob chatbot, carrying the HTTP status code
 * that the controller must send back to the browser.
 */
class BobException extends RuntimeException
{
    /** @var int HTTP status code to return */
    private int $httpStatus;

    /**
     * @param string $message    User-facing error message
     * @param int    $httpStatus HTTP status code to return
     */
    public function __construct(string $message, int $httpStatus = 500)
    {
        parent::__construct($message);
        $this->httpStatus = $httpStatus;
    }

    /**
     * Returns the HTTP status code associated with this error.
     *
     * @return int
     */
    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }
}
