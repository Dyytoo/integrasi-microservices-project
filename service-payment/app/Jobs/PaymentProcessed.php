<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log; // Import Log facade
use App\Models\Payment; // Assuming Payment model might be needed

class PaymentProcessed implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $paymentData;

    /**
     * Create a new job instance.
     *
     * @param array $paymentData
     * @return void
     */
    public function __construct(array $paymentData)
    {
        $this->paymentData = $paymentData;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        // Example logic: Log the payment data received and simulate processing
        Log::info("Payment Processed Event Received:", $this->paymentData);

        // Simulate payment processing (e.g., interacting with a gateway, updating status)
        // Find the payment record
        $payment = Payment::find($this->paymentData["id"]);
        if ($payment) {
            // Simulate success/failure
            $success = (bool)random_int(0, 1); // Randomly succeed or fail for demo
            if ($success) {
                $payment->status = 'successful';
                $payment->transaction_id = 'txn_' . uniqid(); // Generate a dummy transaction ID
                Log::info("Payment ID: {$payment->id} processed successfully.");
                // Optionally dispatch another event (e.g., PaymentSuccess)
            } else {
                $payment->status = 'failed';
                Log::warning("Payment ID: {$payment->id} processing failed.");
                // Optionally dispatch another event (e.g., PaymentFailed)
            }
            $payment->save();
        } else {
            Log::error("Payment record not found for ID: " . $this->paymentData["id"]);
        }

        // In a real application, this job might:
        // - Call a payment gateway API
        // - Update the order status in service-order (via another message or API call)
        // - Send notifications
    }
}

