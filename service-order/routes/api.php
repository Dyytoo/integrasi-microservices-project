<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OrderController;

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

// Basic health check endpoint
Route::get('/health', fn () => response()->json(['status' => 'order service is running']));

// Public route for checking orders by user ID (could be protected later)
Route::get('/users/{userId}/orders', [OrderController::class, 'getOrdersByUser']);

// Get currently authenticated user (for testing, optional)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Order routes
Route::get('/orders', [OrderController::class, 'index']);
Route::post('/orders', [OrderController::class, 'store']);
Route::get('/orders/{id}', [OrderController::class, 'show']);
Route::put('/orders/{id}', [OrderController::class, 'update']);
Route::delete('/orders/{order}', [OrderController::class, 'destroy']);
Route::get('/orders/user/{userId}', [OrderController::class, 'getOrdersByUser']);
Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus']);

