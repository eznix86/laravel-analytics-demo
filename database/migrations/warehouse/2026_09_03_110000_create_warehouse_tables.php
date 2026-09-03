<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_runs', function (Blueprint $table): void {
            $table->id();
            $table->ulid('run_id')->index();
            $table->string('model')->index();
            $table->string('materialization');
            $table->string('status');
            $table->unsignedBigInteger('rows')->nullable();
            $table->unsignedInteger('duration_ms');
            $table->text('error')->nullable();
            $table->timestamp('synced_at');
        });

        Schema::create('imported_orders', function (Blueprint $table): void {
            $table->unsignedBigInteger('id');
            $table->unsignedBigInteger('customer_id');
            $table->integer('amount');

            $table->unique(['id']);
        });

        Schema::create('events', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('source');
            $table->timestamp('happened_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
        Schema::dropIfExists('analytics_runs');
    }
};
