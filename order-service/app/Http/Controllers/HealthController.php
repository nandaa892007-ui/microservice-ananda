<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    /**
     * Endpoint untuks mengecek status kesehatan layanan order-service.
     * Response: HTTP 200 {"status": "ok", "service": "order-service"}
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'order-service',
        ], 200);
    }
}
