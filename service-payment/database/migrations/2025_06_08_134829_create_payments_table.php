<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
           $table->id();
            $table->unsignedBigInteger('order_id'); // Assuming order ID comes from service-order (or passed in request/event)
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('pending'); // e.g., pending, successful, failed
            $table->string('transaction_id')->nullable()->unique(); // Optional: ID from payment gateway
            $table->timestamps();

            // Optional: Add foreign key constraint if orders table exists here
            // $table->foreign(\'order_id\')->references(\'id\')->on(\'orders\');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
