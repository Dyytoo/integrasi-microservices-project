<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;

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

Route::get('/health', function ( ) {return response()->json(['status' => 'ok']);});
Route::get('/products/health', fn () => response()->json(['status' => 'product service is running']));


// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::get('products/{id}/stock', [ProductController::class, 'checkStock']);
Route::apiResource('products', ProductController::class);
Route::put('products/{id}/reduce-stock', [ProductController::class, 'reduceStock']);
Route::get('products/{id}', [ProductController::class, 'show']);

// Publicly accessible product routes
// Default user route (might not be relevant here)
// Route::middleware(\'auth:sanctum\')->get(\'/user\', function (Request $request) {
//     return $request->user();
// });

