<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table): void {
            $table->id('store_id');
            $table->unsignedInteger('sqft');
            $table->string('country', 2);
            $table->string('region');
            $table->boolean('is_active')->default(true);
            $table->date('ds');
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id('product_id');
            $table->string('category');
            $table->string('brand');
        });

        Schema::create('transactions', function (Blueprint $table): void {
            $table->id('transaction_id');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('price');
            $table->timestamp('created_at');
        });

        Schema::create('visits', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('store_id');
            $table->timestamp('ts');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('products');
        Schema::dropIfExists('stores');
    }
};
