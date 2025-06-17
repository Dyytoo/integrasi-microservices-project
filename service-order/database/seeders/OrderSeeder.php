<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $orders = [
            [
                'user_id' => 1, // Admin user
                'product_id' => 1, // Laptop Gaming
                'quantity' => 1,
                'total_price' => 15000000,
                'status' => 'successful',
            ],
            [
                'user_id' => 2, // John Doe
                'product_id' => 2, // Smartphone X
                'quantity' => 2,
                'total_price' => 10000000,
                'status' => 'pending',
            ],
            [
                'user_id' => 3, // Jane Smith
                'product_id' => 3, // Wireless Headphones
                'quantity' => 1,
                'total_price' => 2000000,
                'status' => 'failed',
            ],
            [
                'user_id' => 4, // Bob Johnson
                'product_id' => 4, // Smart Watch
                'quantity' => 3,
                'total_price' => 4500000,
                'status' => 'successful',
            ],
            [
                'user_id' => 1, // Admin user
                'product_id' => 5, // Tablet Pro
                'quantity' => 1,
                'total_price' => 300000,
                'status' => 'pending',
            ],
            [
                'user_id' => 2, // John Doe
                'product_id' => 3, // Wireless Headphones
                'quantity' => 1,
                'total_price' => 450000,
                'status' => 'successful',
            ],
            [
                'user_id' => 3, // Jane Smith
                'product_id' => 4, // Smart Watch
                'quantity' => 1,
                'total_price' => 175000,
                'status' => 'failed',
            ],
            [
                'user_id' => 4, // Bob Johnson
                'product_id' => 2, // Smartphone X
                'quantity' => 1,
                'total_price' => 225000,
                'status' => 'successful',
            ],
            [
                'user_id' => 1, // Admin user
                'product_id' => 3, // Wireless Headphones
                'quantity' => 1,
                'total_price' => 90000,
                'status' => 'pending',
            ],
            [
                'user_id' => 2, // John Doe
                'product_id' => 4, // Smart Watch
                'quantity' => 1,
                'total_price' => 350000,
                'status' => 'successful',
            ],
        ];

        foreach ($orders as $order) {
            Order::create($order);
        }
    }
}
