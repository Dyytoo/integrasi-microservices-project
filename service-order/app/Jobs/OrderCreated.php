<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log; // Import Log facade

class OrderCreated implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $orderData;

    /**
     * Create a new job instance.
     *
     * @param array $orderData
     * @return void
     */
    public function __construct(array $orderData)
    {
        $this->orderData = $orderData;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        // Example logic: Log the order data received from the queue
        Log::info("Order Created Event Received:", $this->orderData);

        // In a real application, this job might:
        // - Trigger payment processing (in service-payment)
        // - Update inventory (in service-product)
        // - Send notifications, etc.
    }
}

