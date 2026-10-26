<?php

namespace App\Clients;

use App\Exceptions\ServiceUnavailableException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductServiceClient
{
    private string $baseUrl;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.product.url', env('PRODUCT_SERVICE_URL', 'http://product-service:3000')), '/');
        $this->timeout = (int) config('services.product.timeout', 5);
    }

    /**
     * Mengambil detail produk dari product-service (GET /products/{id}).
     *
     * @param int $productId
     * @return array|null Mengembalikan data produk jika ada, null jika 404
     * @throws ServiceUnavailableException Jika koneksi gagal, timeout, atau service mati (503)
     */
    public function getProduct(int $productId): ?array
    {
        $url = "{$this->baseUrl}/products/{$productId}";

        try {
            $response = Http::timeout($this->timeout)->get($url);

            if ($response->status() === 404) {
                return null;
            }

            if ($response->successful()) {
                return $response->json('data');
            }

            Log::warning("Product service mengembalikan status tidak sukses", [
                'endpoint' => $url,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new ServiceUnavailableException("Product service tidak dapat dihubungi, coba lagi nanti");
        } catch (ServiceUnavailableException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error("Gagal terhubung ke product-service (timeout atau down)", [
                'endpoint' => $url,
                'error' => $e->getMessage(),
            ]);

            throw new ServiceUnavailableException("Product service tidak dapat dihubungi, coba lagi nanti");
        }
    }
}
