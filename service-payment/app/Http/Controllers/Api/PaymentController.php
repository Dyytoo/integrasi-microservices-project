<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Jobs\PaymentProcessed;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $orderServiceUrl;
    protected $client;

    public function __construct()
    {
        $this->orderServiceUrl = env('ORDER_SERVICE_URL', 'http://service-order:8000');
        $this->client = new Client([
            'timeout' => 5,
            'connect_timeout' => 2
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $payments = Payment::all();
        return response()->json($payments);
    }

    /**
     * Store a newly created resource in storage.
     * This simulates initiating a payment process.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            // Get order details from order service
            $orderResponse = $this->client->get("{$this->orderServiceUrl}/api/orders/{$request->order_id}");
            $order = json_decode($orderResponse->getBody(), true);

            // Check if payment already exists for this order
            $existingPayment = Payment::where('order_id', $request->order_id)->first();
            if ($existingPayment) {
                return response()->json([
                    'message' => 'Payment already exists for this order',
                    'payment' => $existingPayment
                ], 422);
            }

            // Create payment with order's total_price
            $payment = Payment::create([
                'order_id' => $request->order_id,
                'amount' => $order['total_price'],
                'status' => Payment::STATUS_PENDING
            ]);

            // Dispatch job to RabbitMQ
            PaymentProcessed::dispatch($payment->toArray())->onQueue('payment_queue');

            return response()->json($payment, 201);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                return response()->json([
                    'message' => 'Order tidak ditemukan',
                    'error' => 'Order dengan ID ' . $request->order_id . ' tidak ditemukan dalam sistem'
                ], 404);
            }
            Log::error('Failed to create payment: ' . $e->getMessage());
            return response()->json([
                'message' => 'Gagal membuat pembayaran',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            Log::error('Failed to create payment: ' . $e->getMessage());
            return response()->json([
                'message' => 'Gagal membuat pembayaran',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPaymentByOrder($orderId)
    {
        $payment = Payment::where('order_id', $orderId)->first();

        if (!$payment) {
            return response()->json(['message' => 'Payment not found for this order'], 404);
        }

        return response()->json($payment);
    }

    public function updateStatus(Request $request, Payment $payment)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:' . implode(',', Payment::getStatuses()),
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            // Update payment status
            $payment->update(['status' => $request->status]);

            // Update order status in order service
            $this->client->put("{$this->orderServiceUrl}/api/orders/{$payment->order_id}/status", [
                'json' => ['status' => $request->status]
            ]);

            return response()->json($payment);
        } catch (\Exception $e) {
            Log::error('Failed to update payment status: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update payment status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json([
                'message' => 'Payment tidak ditemukan',
                'error' => 'Payment dengan ID ' . $id . ' tidak ditemukan dalam sistem'
            ], 404);
        }

        return response()->json($payment);
    }

    /**
     * Remove the specified payment from storage.
     */
    public function destroy($id)
    {
        try {
            $payment = Payment::find($id);

            if (!$payment) {
                return response()->json([
                    'message' => 'Payment tidak ditemukan',
                    'error' => 'Payment dengan ID ' . $id . ' tidak ditemukan dalam sistem'
                ], 404);
            }

            $payment->delete();
            return response()->json([
                'message' => 'Payment berhasil dihapus',
                'payment_id' => $payment->id
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menghapus payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Update and Destroy methods are removed as they were excluded in the route definition
    // public function update(Request $request, string $id) {}
    // public function destroy(string $id) {}
}

