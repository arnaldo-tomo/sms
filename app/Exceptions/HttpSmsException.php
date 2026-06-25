<?php

namespace App\Exceptions;

use Exception;

class HttpSmsException extends Exception
{
    public function __construct(
        string $message,
        public readonly ?int $statusCode = null,
        public readonly mixed $response = null,
    ) {
        parent::__construct($message, $statusCode ?? 0);
    }
}
