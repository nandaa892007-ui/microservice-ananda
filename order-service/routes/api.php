<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

// Endpoint health check
Route::get('/health', [HealthController::class, 'index']);

// Endpoint checkout pesanan
Route::post('/orders', [OrderController::class, 'store']);
