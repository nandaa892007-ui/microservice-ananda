<?php

namespace App\Exceptions;

use RuntimeException;

class ProductNotFoundException extends RuntimeException
{
    public function __construct(string $message = "Produk tidak ditemukan di product-service")
    {
        parent::__construct($message);
    }
}
