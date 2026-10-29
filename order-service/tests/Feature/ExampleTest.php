<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test endpoint health check order-service.
     */
    public function test_health_check_endpoint_returns_ok(): void
    {
        $response = $this->get('/health');

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'ok',
                     'service' => 'order-service',
                 ]);
    }

    /**
     * Test validasi gagal saat checkout tanpa user_id (HTTP 422).
     */
    public function test_checkout_fails_validation_when_user_id_is_missing(): void
    {
        $response = $this->postJson('/orders', []);

        $response->assertStatus(422)
                 ->assertJson([
                     'message' => 'Data yang dikirim tidak valid',
                     'errors' => [
                         'user_id' => [
                             'Field user_id wajib diisi',
                         ],
                     ],
                 ]);
    }

    /**
     * Test ambil detail pesanan yang tidak ditemukan mengembalikan 404.
     */
    public function test_get_order_detail_returns_404_when_not_found(): void
    {
        $response = $this->getJson('/orders/99999');

        $response->assertStatus(404)
                 ->assertJson([
                     'message' => 'Pesanan dengan id 99999 tidak ditemukan',
                 ]);
    }

    /**
     * Test ambil detail pesanan dengan ID bukan angka mengembalikan 404.
     */
    public function test_get_order_detail_returns_404_when_id_is_not_numeric(): void
    {
        $response = $this->getJson('/orders/invalid-id');

        $response->assertStatus(404);
    }
}
