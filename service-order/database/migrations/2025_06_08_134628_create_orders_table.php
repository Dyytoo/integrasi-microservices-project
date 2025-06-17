<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Assuming user ID comes from service-auth (or passed in request)
            $table->unsignedBigInteger('product_id'); // Assuming product ID comes from service-product (or passed in request)
            $table->integer('quantity');
            $table->decimal('total_price', 10, 2);
            $table->string('status')->default('pending'); // e.g., pending,
            $table->timestamps();

            // Optional: Add foreign key constraints if IDs are managed within this service's DB
            // $table->foreign('user_id')->references('id')->on('users'); // If users table exists here
            // $table->foreign('product_id')->references('id')->on('products'); // If products table exists here
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
