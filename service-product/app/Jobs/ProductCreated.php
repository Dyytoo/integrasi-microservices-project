<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProductCreated implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $productData;

    /**
     * Create a new job instance.
     *
     * @param mixed $productData - Accept both array and object
     * @return void
     */
    public function __construct($productData)
    {
        // Convert to array if needed for consistency
        $this->productData = is_array($productData) ? $productData : (array) $productData;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        foreach ($this->productData as $key => $value) {
            if (is_array($value)) {
                Log::warning("Field {$key} is an array, expected string. Value: ", $value);
            }
        }

        Log::info("Product Created Event Received:", $this->productData);
    }

    public function __serialize(): array
    {
        return ['productData' => $this->productData];
    }

    public function __unserialize(array $data): void
    {
        $this->productData = $data['productData'];
    }
}