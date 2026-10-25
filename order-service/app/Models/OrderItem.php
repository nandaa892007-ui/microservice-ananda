<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'unit_price',
        'quantity',
        'subtotal',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'unit_price' => 'integer',
        'quantity' => 'integer',
        'subtotal' => 'integer',
    ];

    /**
     * Sembunyikan field internal saat serialisasi JSON,
     * agar struktur items persis seperti kontrak spesifikasi UTS:
     * product_id, product_name, unit_price, quantity, subtotal.
     */
    protected $hidden = [
        'id',
        'order_id',
        'created_at',
        'updated_at',
    ];

    /**
     * Relasi belongsTo ke Order.
     * Setiap item pesanan merujuk ke satu pesanan induk.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
