<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 8)->unique();
            $table->string('name');
            $table->string('type');
        });

        Schema::create('journal_entries', function (Blueprint $table): void {
            $table->id();
            $table->date('txn_date');
            $table->string('description');
            $table->boolean('posted')->default(true);
        });

        Schema::create('journal_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('journal_entry_id');
            $table->foreignId('account_id');
            $table->unsignedBigInteger('debit')->default(0);
            $table->unsignedBigInteger('credit')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('accounts');
    }
};
