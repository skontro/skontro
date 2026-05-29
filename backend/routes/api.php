<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CustomerController;
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

        // Customers — reads for any authenticated tenant user
        Route::get('/customers', [CustomerController::class, 'index']);
        Route::get('/customers/{customer}', [CustomerController::class, 'show']);

        // Customer writes — at least admin role
        Route::middleware('role:admin')->group(function () {
            Route::post('/customers', [CustomerController::class, 'store']);
            Route::patch('/customers/{customer}', [CustomerController::class, 'update']);
            Route::delete('/customers/{customer}', [CustomerController::class, 'destroy']);
            Route::post('/customers/{uuid}/restore', [CustomerController::class, 'restore']);
        });
    });
});
