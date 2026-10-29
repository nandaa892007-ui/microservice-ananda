<?php

namespace Tests\Feature;

use App\Models\Order;
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

    /**
     * Test ambil daftar pesanan user mengembalikan status 200.
     */
    public function test_get_user_orders_returns_200(): void
    {
        $response = $this->getJson('/orders/user/7');

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Berhasil mengambil daftar pesanan',
                     'data' => [],
                 ]);
    }

    /**
     * Test filter status tidak valid pada daftar pesanan user mengembalikan 422.
     */
    public function test_get_user_orders_with_invalid_status_returns_422(): void
    {
        $response = $this->getJson('/orders/user/7?status=SHIPPED');

        $response->assertStatus(422)
                 ->assertJson([
                     'message' => 'Data yang dikirim tidak valid',
                     'errors' => [
                         'status' => [
                             'Status harus PENDING atau PAID',
                         ],
                     ],
                 ]);
    }

    /**
     * Test bayar pesanan yang tidak ditemukan mengembalikan 404.
     */
    public function test_pay_order_returns_404_when_not_found(): void
    {
        $response = $this->patchJson('/orders/99999/pay');

        $response->assertStatus(404)
                 ->assertJson([
                     'message' => 'Pesanan dengan id 99999 tidak ditemukan',
                 ]);
    }

    /**
     * Test bayar pesanan sukses mengubah status menjadi PAID (200).
     */
    public function test_pay_order_success_changes_status_to_paid(): void
    {
        $order = Order::create([
            'user_id' => 7,
            'status' => Order::STATUS_PENDING,
            'total_amount' => 150000,
        ]);

        $response = $this->patchJson("/orders/{$order->id}/pay");

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Pesanan berhasil dibayar',
                     'data' => [
                         'id' => $order->id,
                         'status' => 'PAID',
                     ],
                 ]);
    }

    /**
     * Test bayar pesanan yang sudah PAID mengembalikan 409.
     */
    public function test_pay_order_already_paid_returns_409(): void
    {
        $order = Order::create([
            'user_id' => 7,
            'status' => Order::STATUS_PAID,
            'total_amount' => 150000,
        ]);

        $response = $this->patchJson("/orders/{$order->id}/pay");

        $response->assertStatus(409)
                 ->assertJson([
                     'message' => 'Pesanan berstatus PAID, hanya pesanan PENDING yang dapat dibayar',
                 ]);
    }
}
