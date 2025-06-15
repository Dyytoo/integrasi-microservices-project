<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $payments = [
            [
                'order_id' => 1,
                'amount' => 15000000.00,
                'status' => 'successful',
                'transaction_id' => 'TRX-' . Str::upper(Str::random(10)),
                'created_at' => Carbon::now()->subDays(5),
                'updated_at' => Carbon::now()->subDays(5),
            ],
            [
                'order_id' => 2,
                'amount' => 10000000.00,
                'status' => 'pending',
                'transaction_id' => 'TRX-' . Str::upper(Str::random(10)),
                'created_at' => Carbon::now()->subDays(4),
                'updated_at' => Carbon::now()->subDays(4),
            ],
            [
                'order_id' => 3,
                'amount' => 2000000.00,
                'status' => 'failed',
                'transaction_id' => 'TRX-' . Str::upper(Str::random(10)),
                'created_at' => Carbon::now()->subDays(3),
                'updated_at' => Carbon::now()->subDays(3),
            ],
            [
                'order_id' => 4,
                'amount' => 4500000.00,
                'status' => 'successful',
                'transaction_id' => 'TRX-' . Str::upper(Str::random(10)),
                'created_at' => Carbon::now()->subDays(2),
                'updated_at' => Carbon::now()->subDays(2),
            ],
            [
                'order_id' => 5,
                'amount' => 300000.00,
                'status' => 'pending',
                'transaction_id' => 'TRX-' . Str::upper(Str::random(10)),
                'created_at' => Carbon::now()->subDays(1),
                'updated_at' => Carbon::now()->subDays(1),
            ],
            [
                'order_id' => 6,
                'amount' => 450000.00,
                'status' => 'successful',
                'transaction_id' => 'TRX-' . Str::upper(Str::random(10)),
                'created_at' => Carbon::now()->subHours(12),
                'updated_at' => Carbon::now()->subHours(12),
            ],
            [
                'order_id' => 7,
                'amount' => 175000.00,
                'status' => 'failed',
                'transaction_id' => 'TRX-' . Str::upper(Str::random(10)),
                'created_at' => Carbon::now()->subHours(6),
                'updated_at' => Carbon::now()->subHours(6),
            ],
            [
                'order_id' => 8,
                'amount' => 225000.00,
                'status' => 'successful',
                'transaction_id' => 'TRX-' . Str::upper(Str::random(10)),
                'created_at' => Carbon::now()->subHours(3),
                'updated_at' => Carbon::now()->subHours(3),
            ],
            [
                'order_id' => 9,
                'amount' => 90000.00,
                'status' => 'pending',
                'transaction_id' => 'TRX-' . Str::upper(Str::random(10)),
                'created_at' => Carbon::now()->subHours(2),
                'updated_at' => Carbon::now()->subHours(2),
            ],
            [
                'order_id' => 10,
                'amount' => 350000.00,
                'status' => 'successful',
                'transaction_id' => 'TRX-' . Str::upper(Str::random(10)),
                'created_at' => Carbon::now()->subHour(),
                'updated_at' => Carbon::now()->subHour(),
            ],
        ];

        DB::table('payments')->insert($payments);
    }
}
