<?php

namespace App\Http\Controllers;

use App\Exceptions\EmptyCartException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\OrderNotFoundException;
use App\Exceptions\ProductNotFoundException;
use App\Exceptions\ServiceUnavailableException;
use App\Http\Requests\ListUserOrdersRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService)
    {
    }

    /**
     * Endpoint checkout: POST /orders
     * Mengubah isi keranjang belanja user menjadi pesanan baru.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            $userId = (int) $request->validated('user_id');
            $order = $this->orderService->checkout($userId);

            return response()->json([
                'message' => 'Pesanan berhasil dibuat',
                'data' => $order,
            ], 201);

        } catch (EmptyCartException | ProductNotFoundException | InsufficientStockException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 409);

        } catch (ServiceUnavailableException $e) {
            Log::warning("Service eksternal tidak dapat dihubungi saat checkout", [
                'error' => $e->getMessage(),
            ]);

            $body = ['message' => $e->getMessage()];
            if (config('app.debug')) {
                $body['debug_detail'] = $e->getMessage();
            }

            return response()->json($body, 503);

        } catch (Throwable $e) {
            return $this->serverErrorResponse($e, 'membuat pesanan');
        }
    }

    /**
     * Endpoint detail pesanan: GET /orders/{id}
     * Menampilkan detail satu pesanan beserta relasi item-itemnya.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $order = $this->orderService->getOrder($id);

            return response()->json([
                'message' => 'Berhasil mengambil pesanan',
                'data' => $order,
            ], 200);

        } catch (OrderNotFoundException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 404);

        } catch (Throwable $e) {
            return $this->serverErrorResponse($e, 'mengambil pesanan');
        }
    }

    /**
     * Endpoint daftar pesanan satu user: GET /orders/user/{userId}
     * Menampilkan riwayat pesanan milik user (terbaru di atas) dengan filter opsional ?status=.
     *
     * Respon sukses: HTTP 200
     * Respon gagal:
     * - 422: Nilai status tidak valid (bukan PENDING atau PAID)
     * - 404: userId di path bukan angka
     * - 500: Error tak terduga pada server
     */
    public function userOrders(ListUserOrdersRequest $request, int $userId): JsonResponse
    {
        try {
            $status = $request->query('status');
            $orders = $this->orderService->getUserOrders($userId, $status);

            return response()->json([
                'message' => 'Berhasil mengambil daftar pesanan',
                'data' => $orders,
            ], 200);

        } catch (Throwable $e) {
            return $this->serverErrorResponse($e, 'mengambil daftar pesanan');
        }
    }

    /**
     * Format response seragam untuk penanganan error internal server (500).
     * Detail teknis hanya dimunculkan jika APP_DEBUG=true.
     */
    private function serverErrorResponse(Throwable $e, string $action): JsonResponse
    {
        Log::error("Gagal {$action}", [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        $body = ['message' => "Terjadi kesalahan internal saat {$action}"];

        if (config('app.debug')) {
            $body['error'] = $e->getMessage();
            $body['location'] = $e->getFile() . ':' . $e->getLine();
        }

        return response()->json($body, 500);
    }
}
