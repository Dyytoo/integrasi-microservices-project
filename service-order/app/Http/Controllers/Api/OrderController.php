<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Jobs\OrderCreated;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    protected $authServiceUrl;
    protected $productServiceUrl;
    protected $client;

    public function __construct()
    {
        $this->authServiceUrl = env('AUTH_SERVICE_URL', 'http://service-auth:8000');
        $this->productServiceUrl = env('PRODUCT_SERVICE_URL', 'http://service-product:8000');
        $this->client = new Client([
            'timeout' => 5, // 5 detik timeout
            'connect_timeout' => 2 // 2 detik connection timeout
        ]);
    }



    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $orders = Order::all();

        // Get all unique product IDs from orders
        $productIds = $orders->pluck('product_id')->unique()->toArray();

        // Fetch products from service-product
        try {
            $productResponse = $this->client->get("{$this->productServiceUrl}/api/products", [
                'query' => ['ids' => implode(',', $productIds)]
            ]);
            $products = collect(json_decode($productResponse->getBody(), true));

            // Map products to orders
            $orders = $orders->map(function ($order) use ($products) {
                $order->product = $products->firstWhere('id', $order->product_id);
                return $order;
            });
        } catch (\Exception $e) {
            \Log::error('Failed to fetch products: ' . $e->getMessage());
            // Continue without product data if there's an error
        }

        return response()->json($orders);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'product_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            // Verifikasi user exists di service-auth
            try {
                $userResponse = $this->client->get("{$this->authServiceUrl}/api/users/{$request->user_id}");
                // Jika tidak error, user ditemukan
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                if ($e->getResponse()->getStatusCode() === 404) {
                    return response()->json(['message' => 'User tidak ditemukan'], 404);
                }
                throw $e; // Re-throw untuk ditangkap di catch utama
            }

            // Verifikasi product exists dan cek stok
            try {
                $productResponse = $this->client->get("{$this->productServiceUrl}/api/products/{$request->product_id}");
                $product = json_decode($productResponse->getBody(), true);
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                if ($e->getResponse()->getStatusCode() === 404) {
                    return response()->json(['message' => 'Produk tidak ditemukan'], 404);
                }
                throw $e; // Re-throw untuk ditangkap di catch utama
            }

            // Cek stok produk
            if ($product['stock'] < $request->quantity) {
                return response()->json([
                    'message' => 'Stok produk tidak mencukupi',
                    'available_stock' => $product['stock']
                ], 422);
            }

            // Hitung total_price berdasarkan harga produk dan quantity
            $total_price = $product['price'] * $request->quantity;

            // Mulai transaksi database
            \DB::beginTransaction();

            try {
                // Buat order dengan total_price yang sudah dihitung
                $order = Order::create([
                    'user_id' => $request->user_id,
                    'product_id' => $request->product_id,
                    'quantity' => $request->quantity,
                    'total_price' => $total_price,
                    'status' => 'pending'
                ]);

                // Kurangi stok produk dengan idempotency key untuk menghindari pengurangan ganda
                $idempotencyKey = md5($order->id . '_' . time());

                $userResponse = $this->callWithRetry('get', "{$this->authServiceUrl}/api/users/{$request->user_id}");
                $productResponse = $this->callWithRetry('get', "{$this->productServiceUrl}/api/products/{$request->product_id}");
                $stockResponse = $this->callWithRetry('put', "{$this->productServiceUrl}/api/products/{$request->product_id}/reduce-stock", [
                    'json' => [
                        'quantity' => $request->quantity,
                        'idempotency_key' => $idempotencyKey
                    ]
                ]);

                // Jika berhasil, commit transaksi
                \DB::commit();

                // Dispatch job ke RabbitMQ
                OrderCreated::dispatch($order->toArray())->onQueue('order_queue');

                return response()->json($order, 201);
            } catch (\Exception $e) {
                // Jika gagal mengurangi stok, rollback transaksi
                \DB::rollBack();
                \Log::error('Failed to reduce product stock: ' . $e->getMessage());
                return response()->json(['message' => 'Gagal mengurangi stok produk: ' . $e->getMessage()], 500);
            }
        } catch (\Exception $e) {
            // Jika terjadi error lain, pastikan transaksi di-rollback jika ada
            if (\DB::transactionLevel() > 0) {
                \DB::rollBack();
            }
            \Log::error('Failed to process order: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal memproses order: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $order = Order::find($id);

            if (!$order) {
                return response()->json([
                    'message' => 'Order tidak ditemukan',
                    'error' => 'Order dengan ID ' . $id . ' tidak ditemukan dalam sistem'
                ], 404);
            }

            // Fetch product data from product service
            $productResponse = $this->client->get("{$this->productServiceUrl}/api/products/{$order->product_id}");
            $product = json_decode($productResponse->getBody(), true);
            $order->product = $product;

            // Fetch user data from auth service
            $userResponse = $this->client->get("{$this->authServiceUrl}/api/users/{$order->user_id}");
            $user = json_decode($userResponse->getBody(), true);
            $order->user = $user;

            return response()->json($order);
        } catch (\Exception $e) {
            \Log::error('Failed to fetch order details: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to fetch order details: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            $order = Order::findOrFail($id);
            $oldQuantity = $order->quantity;

            // Get product data
            $product = $order->getProduct();
            if (!$product) {
                return response()->json([
                    'message' => 'Product not found'
                ], 404);
            }

            // Calculate new total price based on product price and new quantity
            $newTotalPrice = $product['price'] * $request->quantity;

            // Check if requested quantity exceeds available stock
            if ($request->quantity > $product['stock'] + $oldQuantity) {
                return response()->json([
                    'message' => 'Stok produk tidak mencukupi',
                    'available_stock' => $product['stock'] + $oldQuantity
                ], 422);
            }

            // Update order quantity and total price
            $order->quantity = $request->quantity;
            $order->total_price = $newTotalPrice;
            $order->save();

            // Calculate stock difference
            $difference = $oldQuantity - $request->quantity;

            // Update product stock
            if (!$order->updateProductStock($difference)) {
                // If stock update fails, revert order quantity and total price
                $order->quantity = $oldQuantity;
                $order->total_price = $product['price'] * $oldQuantity;
                $order->save();

                return response()->json([
                    'message' => 'Failed to update product stock'
                ], 500);
            }

            // Get updated data
            $product = $order->getProduct();
            $user = $order->getUser();

            // Add product and user data to response
            $order->product = $product;
            $order->user = $user;

            return response()->json([
                'message' => 'Order updated successfully',
                'order' => $order
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */

    public function getOrdersByUser($userId)
    {
        $orders = Order::where('user_id', $userId)->with(['user', 'product'])->get();
        return response()->json($orders);
    }


    public function destroy(Order $order) // Use route model binding
    {
        try {
            $order->delete();
            return response()->json([
                'message' => 'Order berhasil dihapus',
                'order_id' => $order->id
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Order tidak ditemukan',
                'error' => 'Order dengan ID tersebut tidak ditemukan dalam sistem'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menghapus order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function callWithRetry($method, $url, $options = [], $maxAttempts = 3)
    {
        $attempts = 0;
        $lastException = null;

        while ($attempts < $maxAttempts) {
            try {
                return $this->client->request($method, $url, $options);
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                $attempts++;
                $lastException = $e;

                if ($attempts >= $maxAttempts) {
                    throw $e;
                }

                // Delay sebelum retry (exponential backoff sederhana)
                usleep(100000 * $attempts); // 100ms, 200ms, 300ms
            }
        }

        throw $lastException;
    }

    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:pending,processing,paid,failed,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            $order = Order::findOrFail($id);
            $order->update(['status' => $request->status]);

            return response()->json($order);
        } catch (\Exception $e) {
            Log::error('Failed to update order status: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update order status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}

