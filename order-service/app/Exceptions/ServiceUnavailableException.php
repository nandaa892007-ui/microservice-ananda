<?php

namespace App\Exceptions;

use RuntimeException;

class ServiceUnavailableException extends RuntimeException
{
    public function __construct(string $message = "Service lain tidak dapat dihubungi, coba lagi nanti")
    {
        parent::__construct($message);
    }
}
