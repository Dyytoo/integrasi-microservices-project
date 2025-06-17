<?php

namespace App\Models;

use Illuminate\Support\Facades\Http;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = [];
    protected $table = null; // Prevent database queries

    public static function find($id)
    {
        $response = Http::get(config('services.product.url') . '/api/products/' . $id);

        if ($response->successful()) {
            $data = $response->json();
            $product = new static($data);
            $product->exists = true; // Mark as existing record
            return $product;
        }

        return null;
    }

    public function updateStock($quantity)
    {
        $response = Http::put(config('services.product.url') . '/api/products/' . $this->id, [
            'quantity' => $quantity
        ]);

        return $response->successful();
    }

    /**
     * Get the orders for the product.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Prevent eager loading
     */
    public function newQuery()
    {
        return $this->newModelQuery();
    }

    /**
     * Prevent eager loading
     */
    public function getRelationValue($key)
    {
        return null;
    }
}
