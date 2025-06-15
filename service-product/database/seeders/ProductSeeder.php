<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;


class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'name' => 'Laptop Gaming',
                'description' => 'High-performance gaming laptop with RTX 3080',
                'price' => 15000000,
                'stock' => 10,
            ],
            [
                'name' => 'Smartphone X',
                'description' => 'Latest smartphone with 5G capability',
                'price' => 5000000,
                'stock' => 20,
            ],
            [
                'name' => 'Wireless Headphones',
                'description' => 'Noise-cancelling wireless headphones',
                'price' => 2000000,
                'stock' => 15,
            ],
            [
                'name' => 'Smart Watch',
                'description' => 'Fitness tracker and smart watch',
                'price' => 1500000,
                'stock' => 25,
            ],
            [
                'name' => 'Tablet Pro',
                'description' => 'Professional tablet for work and entertainment',
                'price' => 8000000,
                'stock' => 8,
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
