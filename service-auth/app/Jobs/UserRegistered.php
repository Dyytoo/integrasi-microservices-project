<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log; // Import Log facade

class UserRegistered implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userData;

    /**
     * Create a new job instance.
     *
     * @param array $userData
     * @return void
     */
    public function __construct(array $userData)
    {
        $this->userData = $userData;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        // Example logic: Log the user data received from the queue
        Log::info("User Registered Event Received:", $this->userData);

        // In a real application, you might:
        // - Send a welcome email
        // - Notify other services
        // - Update analytics, etc.
    }
}

