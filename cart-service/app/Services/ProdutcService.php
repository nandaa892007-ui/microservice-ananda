<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable; // Perbaiki typo dan pastikan penulisannya benar

class ProductService
{
    public function getProduct(int $productId): ?array
    {
        try {
            // Perbaiki penggabungan URL dan hilangkan double Http::get()
            $baseUrl = rtrim(config('services.product.url'), '/');
            $response = Http::get("{$baseUrl}/products/{$productId}");

            if ($response->successful() && isset($response['data'])) {
                return $response['data'];
            }
            
            return null;
        } catch (Throwable $e) {
            // Perbaiki penulisan kutip pada Log::error
            Log::error("Gagal mengambil data produk", [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);
            
            return null;
        }
    }
}