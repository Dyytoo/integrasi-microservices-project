<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'stock',
    ];

    // // Debug: Log saat model dibuat
    // protected static function boot()
    // {
    //     parent::boot();

    //     static::creating(function ($product) {
    //         Log::info('Product model creating event triggered');
    //         Log::info('Product data in creating event:', $product->toArray());
    //     });

    //     static::created(function ($product) {
    //         Log::info('Product model created event triggered');
    //         Log::info('Product data in created event:', $product->toArray());
    //     });
    // }
}
