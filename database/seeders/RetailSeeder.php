<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\Visit;
use Illuminate\Database\Seeder;

class RetailSeeder extends Seeder
{
    public function run(): void
    {
        Store::query()->insert([
            ['store_id' => 1, 'sqft' => 800, 'country' => 'MU', 'region' => 'Indian Ocean', 'is_active' => true, 'ds' => '2026-01-01'],
            ['store_id' => 2, 'sqft' => 3000, 'country' => 'GB', 'region' => 'London', 'is_active' => true, 'ds' => '2026-01-01'],
            ['store_id' => 3, 'sqft' => 9000, 'country' => 'FR', 'region' => 'Paris', 'is_active' => true, 'ds' => '2026-01-01'],
            ['store_id' => 4, 'sqft' => 500, 'country' => 'US', 'region' => 'New York', 'is_active' => false, 'ds' => '2026-01-01'],
        ]);

        Product::query()->insert([
            ['product_id' => 1, 'category' => 'shoes', 'brand' => 'Nike'],
            ['product_id' => 2, 'category' => 'shoes', 'brand' => 'Adidas'],
            ['product_id' => 3, 'category' => 'shirts', 'brand' => 'Nike'],
            ['product_id' => 4, 'category' => 'shirts', 'brand' => 'Puma'],
        ]);

        Transaction::query()->insert([
            ['transaction_id' => 1, 'customer_id' => 1, 'store_id' => 1, 'product_id' => 1, 'quantity' => 1, 'price' => 10_000, 'created_at' => '2026-01-10 09:00:00'],
            ['transaction_id' => 2, 'customer_id' => 1, 'store_id' => 1, 'product_id' => 3, 'quantity' => 2, 'price' => 5_000, 'created_at' => '2026-01-10 11:00:00'],
            ['transaction_id' => 3, 'customer_id' => 2, 'store_id' => 1, 'product_id' => 2, 'quantity' => 1, 'price' => 20_000, 'created_at' => '2026-01-10 16:00:00'],
            ['transaction_id' => 4, 'customer_id' => 3, 'store_id' => 1, 'product_id' => 1, 'quantity' => 1, 'price' => 10_000, 'created_at' => '2026-01-11 10:00:00'],
            ['transaction_id' => 5, 'customer_id' => 4, 'store_id' => 2, 'product_id' => 4, 'quantity' => 3, 'price' => 3_000, 'created_at' => '2026-01-10 12:00:00'],
            ['transaction_id' => 6, 'customer_id' => 4, 'store_id' => 2, 'product_id' => 1, 'quantity' => 1, 'price' => 10_000, 'created_at' => '2026-02-05 12:00:00'],
            ['transaction_id' => 7, 'customer_id' => 5, 'store_id' => 2, 'product_id' => 1, 'quantity' => 1, 'price' => 10_000, 'created_at' => '2026-02-05 14:00:00'],
            ['transaction_id' => 8, 'customer_id' => 6, 'store_id' => 3, 'product_id' => 2, 'quantity' => 2, 'price' => 20_000, 'created_at' => '2026-02-06 09:00:00'],
            ['transaction_id' => 9, 'customer_id' => 7, 'store_id' => 4, 'product_id' => 1, 'quantity' => 1, 'price' => 99_999, 'created_at' => '2026-01-10 09:00:00'],
        ]);

        $visits = [];
        foreach ([[1, '2026-01-10', 10], [1, '2026-01-11', 4], [2, '2026-01-10', 5], [2, '2026-02-05', 8], [3, '2026-02-06', 2]] as [$store, $day, $count]) {
            for ($i = 0; $i < $count; $i++) {
                $visits[] = ['customer_id' => $i + 1, 'store_id' => $store, 'ts' => $day.' 08:00:00'];
            }
        }

        Visit::query()->insert($visits);
    }
}
