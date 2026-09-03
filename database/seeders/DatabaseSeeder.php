<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([LedgerSeeder::class, RetailSeeder::class]);

        Customer::query()->insert([
            ['id' => 1, 'name' => 'Ada', 'country' => 'MU'],
            ['id' => 2, 'name' => 'Grace', 'country' => 'GB'],
        ]);

        Order::query()->insert([
            ['customer_id' => 1, 'amount' => 200, 'status' => 'paid', 'placed_at' => '2026-01-14 10:00:00'],
            ['customer_id' => 1, 'amount' => 100, 'status' => 'paid', 'placed_at' => '2026-01-22 10:00:00'],
            ['customer_id' => 1, 'amount' => 450, 'status' => 'paid', 'placed_at' => '2026-02-03 10:00:00'],
            ['customer_id' => 2, 'amount' => 50, 'status' => 'paid', 'placed_at' => '2026-01-09 10:00:00'],
            ['customer_id' => 2, 'amount' => 999, 'status' => 'cancelled', 'placed_at' => '2026-02-11 10:00:00'],
        ]);
    }
}
