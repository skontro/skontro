<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'service' => 'skontro-api',
            'version' => '0.0.0',
            'timestamp' => now()->toIso8601String(),
        ]);
    });

    // Public auth
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Authenticated + tenant-resolved
    Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});
