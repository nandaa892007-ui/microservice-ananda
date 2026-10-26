<?php

namespace App\Clients;

use App\Exceptions\ServiceUnavailableException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class CartServiceClient
{
    private string $baseUrl;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.cart.url', env('CART_SERVICE_URL', 'http://cart-service:3001')), '/');
        $this->timeout = (int) config('services.cart.timeout', 5);
    }

    /**
     * Mengambil isi keranjang user dari cart-service (GET /carts/{userId}).
     *
     * @param int $userId
     * @return array
     * @throws ServiceUnavailableException Jika koneksi gagal, timeout, atau service mati (503)
     */
    public function getCart(int $userId): array
    {
        $url = "{$this->baseUrl}/carts/{$userId}";

        try {
            $response = Http::timeout($this->timeout)->get($url);

            if ($response->successful()) {
                return $response->json('data') ?? [];
            }

            Log::warning("Cart service mengembalikan status tidak sukses", [
                'endpoint' => $url,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new ServiceUnavailableException("Cart service tidak dapat dihubungi, coba lagi nanti");
        } catch (ServiceUnavailableException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error("Gagal terhubung ke cart-service (timeout atau down)", [
                'endpoint' => $url,
                'error' => $e->getMessage(),
            ]);

            throw new ServiceUnavailableException("Cart service tidak dapat dihubungi, coba lagi nanti");
        }
    }

    /**
     * Meminta cart-service mengosongkan keranjang user (DELETE /carts/{userId}).
     * Sesuai aturan: Jika gagal dikosongkan, pesanan tidak boleh dibatalkan,
     * melainkan cukup dicatat di log.
     *
     * @param int $userId
     * @return bool
     */
    public function clearCart(int $userId): bool
    {
        $url = "{$this->baseUrl}/carts/{$userId}";

        try {
            $response = Http::timeout($this->timeout)->delete($url);

            if ($response->successful()) {
                Log::info("Keranjang user {$userId} berhasil dikosongkan");
                return true;
            }

            Log::warning("Gagal mengosongkan keranjang user {$userId} di cart-service", [
                'endpoint' => $url,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (Throwable $e) {
            Log::error("Kegagalan jaringan saat mengosongkan keranjang user {$userId}", [
                'endpoint' => $url,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
