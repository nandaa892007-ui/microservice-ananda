<?php

namespace App\Exceptions;

use RuntimeException;

class OrderNotFoundException extends RuntimeException
{
    public function __construct(int $orderId)
    {
        parent::__construct("Pesanan dengan id {$orderId} tidak ditemukan");
    }
}
