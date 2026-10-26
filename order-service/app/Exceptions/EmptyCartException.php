<?php

namespace App\Exceptions;

use RuntimeException;

class EmptyCartException extends RuntimeException
{
    public function __construct(string $message = "Keranjang belanja kosong")
    {
        parent::__construct($message);
    }
}
