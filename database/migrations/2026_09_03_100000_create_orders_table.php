<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('country', 2);
        });

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id');
            $table->integer('amount');
            $table->string('status');
            $table->timestamp('placed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
        Schema::dropIfExists('customers');
    }
};
