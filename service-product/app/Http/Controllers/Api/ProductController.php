<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Jobs\ProductCreated;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->has('ids')) {
            $ids = explode(',', $request->ids);
            $products = Product::whereIn('id', $ids)->get();
            return response()->json($products);
        }

        $products = Product::all();
        return response()->json($products);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        try {
            // Debug: Log request data
            Log::info('Product store request:', $request->all());

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'required|string|max:1000',
                'price' => 'required|numeric|min:0',
                'stock' => 'required|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $product = Product::create($validator->validated());

            // Dispatch job to RabbitMQ dengan error handling yang lebih detail
            try {
                // Get fresh product data as array
                $productData = $product->fresh()->toArray();

                // Debug: Log data yang akan dikirim
                Log::info('Product data to dispatch:', [
                    'data' => $productData,
                    'data_type' => gettype($productData),
                    'is_array' => is_array($productData)
                ]);

                // Dispatch the job with the product data
                ProductCreated::dispatch($productData)
                    ->onConnection('rabbitmq')
                    ->onQueue('product_queue');

                Log::info('Job dispatched successfully');
            } catch (\Exception $e) {
                Log::error('Error dispatching job:', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);
            }

            return response()->json([
                'message' => 'Product created successfully',
                'product' => $product
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating product: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'message' => 'Error creating product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reduce product stock
     */
    public function reduceStock(Request $request, $id)
    {
        try {
            // Debug: Log request data
            Log::info('Reduce stock request:', ['id' => $id, 'data' => $request->all()]);

            $validator = Validator::make($request->all(), [
                'quantity' => 'required|integer|min:1',
                'idempotency_key' => 'nullable|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();

            // Cek apakah operasi dengan idempotency_key ini sudah pernah dilakukan
            if (!empty($validated['idempotency_key'])) {
                $cacheKey = "stock_reduction:{$id}:{$validated['idempotency_key']}";
                if (Cache::has($cacheKey)) {
                    return response()->json(Cache::get($cacheKey));
                }
            }

            // Proses pengurangan stok
            $product = Product::findOrFail($id);

            if ($product->stock < $validated['quantity']) {
                $response = [
                    'message' => 'Stok tidak mencukupi',
                    'available_stock' => $product->stock,
                    'requested_quantity' => $validated['quantity']
                ];

                // Simpan response ke cache jika ada idempotency_key
                if (!empty($validated['idempotency_key'])) {
                    Cache::put($cacheKey, $response, now()->addHours(24));
                }

                return response()->json($response, 422);
            }

            $product->stock -= $validated['quantity'];
            $product->save();

            $response = [
                'message' => 'Stok berhasil dikurangi',
                'product' => $product,
                'reduced_quantity' => $validated['quantity']
            ];

            // Simpan response ke cache jika ada idempotency_key
            if (!empty($validated['idempotency_key'])) {
                Cache::put($cacheKey, $response, now()->addHours(24));
            }

            return response()->json($response);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Product not found'], 404);
        } catch (\Exception $e) {
            Log::error('Error reducing stock: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'message' => 'Error reducing stock',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check product stock
     */
    public function checkStock($id)
    {
        try {
            $product = Product::find($id);

            if (!$product) {
                return response()->json(['message' => 'Produk tidak ditemukan'], 404);
            }

            return response()->json([
                'product_id' => $product->id,
                'name' => $product->name,
                'stock' => $product->stock,
            ]);

        } catch (\Exception $e) {
            Log::error('Error checking stock: ' . $e->getMessage());
            return response()->json(['message' => 'Error checking stock'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $product = Product::find($id);

            if (!$product) {
                return response()->json([
                    'message' => 'Produk tidak ditemukan'
                ], 404);
            }

            return response()->json($product);

        } catch (\Exception $e) {
            Log::error('Error showing product: ' . $e->getMessage());
            return response()->json(['message' => 'Error fetching product'], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            // Debug: Log request data
            Log::info('Product update request:', ['id' => $id, 'data' => $request->all()]);

            $product = Product::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|required|string|max:1000',
                'price' => 'sometimes|required|numeric|min:0',
                'stock' => 'sometimes|required|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $product->update($validator->validated());

            return response()->json([
                'message' => 'Product updated successfully',
                'product' => $product
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Product not found'], 404);
        } catch (\Exception $e) {
            Log::error('Error updating product: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'message' => 'Error updating product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);
            $product->delete();

            return response()->json([
                'message' => 'Product deleted successfully'
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Product not found'], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting product: ' . $e->getMessage());
            return response()->json(['message' => 'Error deleting product'], 500);
        }
    }
}
