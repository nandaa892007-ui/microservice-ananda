<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

// Endpoint health check
Route::get('/health', [HealthController::class, 'index']);

// Endpoint checkout pesanan
Route::post('/orders', [OrderController::class, 'store']);

// Endpoint detail satu pesanan
Route::get('/orders/{id}', [OrderController::class, 'show'])->whereNumber('id');

// Endpoint daftar pesanan satu user
Route::get('/orders/user/{userId}', [OrderController::class, 'userOrders'])->whereNumber('userId');

// Endpoint simulasi bayar pesanan
Route::patch('/orders/{id}/pay', [OrderController::class, 'pay'])->whereNumber('id');
