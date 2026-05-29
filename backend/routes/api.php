<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\InvoiceActionController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\ProductController;
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

        // Products — reads for any authenticated tenant user
        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/{product}', [ProductController::class, 'show']);

        // Product writes — at least admin role. No destroy: products archive.
        Route::middleware('role:admin')->group(function () {
            Route::post('/products', [ProductController::class, 'store']);
            Route::patch('/products/{product}', [ProductController::class, 'update']);
            Route::post('/products/{product}/archive', [ProductController::class, 'archive']);
            Route::post('/products/{product}/unarchive', [ProductController::class, 'unarchive']);
        });

        // Invoices — reads for any authenticated tenant user
        Route::get('/invoices', [InvoiceController::class, 'index']);
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);

        // Invoice writes + state transitions — at least admin role
        Route::middleware('role:admin')->group(function () {
            Route::post('/invoices', [InvoiceController::class, 'store']);
            Route::patch('/invoices/{invoice}', [InvoiceController::class, 'update']);

            Route::post('/invoices/{invoice}/issue', [InvoiceActionController::class, 'issue']);
            Route::post('/invoices/{invoice}/send', [InvoiceActionController::class, 'send']);
            Route::post('/invoices/{invoice}/payments', [InvoiceActionController::class, 'recordPayment']);
            Route::post('/invoices/{invoice}/cancel', [InvoiceActionController::class, 'cancel']);
        });
    });
});
