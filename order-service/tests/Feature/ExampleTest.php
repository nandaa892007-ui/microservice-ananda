<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
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
}
