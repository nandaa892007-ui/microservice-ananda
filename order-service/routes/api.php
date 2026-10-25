<?php

use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

// Endpoint health check
Route::get('/health', [HealthController::class, 'index']);
