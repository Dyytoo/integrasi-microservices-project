<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'quantity',
        'total_price',
        'status'
    ];

    protected static function booted()
    {
        static::created(function ($order) {
            $product = $order->getProduct();
            if ($product) {
                $order->updateProductStock(-$order->quantity);
            }
        });

        static::updated(function ($order) {
            if ($order->isDirty('quantity')) {
                $product = $order->getProduct();
                if ($product) {
                    $difference = $order->getOriginal('quantity') - $order->quantity;
                    $order->updateProductStock($difference);
                }
            }
        });
    }

    /**
     * Get product data from product service
     */
    public function getProduct()
    {
        $client = new \GuzzleHttp\Client();
        try {
            $response = $client->get(config('services.product.url') . '/api/products/' . $this->product_id);
            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Update product stock
     */
    public function updateProductStock($quantity)
    {
        $client = new \GuzzleHttp\Client();
        try {
            $response = $client->put(config('services.product.url') . '/api/products/' . $this->product_id . '/reduce-stock', [
                'json' => [
                    'quantity' => abs($quantity),
                    'idempotency_key' => md5($this->id . '_' . time())
                ]
            ]);
            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get user data from auth service
     */
    public function getUser()
    {
        $client = new \GuzzleHttp\Client();
        try {
            $response = $client->get(config('services.auth.url') . '/api/users/' . $this->user_id);
            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            return null;
        }
    }
}
