<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PaymentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Health check endpoints
Route::get('/health', fn () => response()->json(['status' => 'ok']));
Route::get('/payments/health', fn () => response()->json(['status' => 'payment service is running']));

// Public routes (for now) - Get payment data
Route::get('/orders/{orderId}/payment', [PaymentController::class, 'getPaymentByOrder']);
Route::put('/payments/{payment}/status', [PaymentController::class, 'updateStatus']);

// Main API resource (create, list, show)
Route::apiResource('payments', PaymentController::class)->only([
    'index', 'show', 'store', 'destroy'
]);

// Optional: test token-based user info (for debugging/testing)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Payment routes
Route::get('/payments', [PaymentController::class, 'index']);
Route::post('/payments', [PaymentController::class, 'store']);
Route::get('/payments/{payment}', [PaymentController::class, 'show']);
Route::get('/payments/order/{orderId}', [PaymentController::class, 'getPaymentByOrder']);
Route::put('/payments/{payment}/status', [PaymentController::class, 'updateStatus']);
