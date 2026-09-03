<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imported_events', function (Blueprint $table): void {
            $table->unsignedBigInteger('id');
            $table->string('name');
            $table->string('source');
            $table->timestamp('happened_at');

            $table->unique(['id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imported_events');
    }
};
