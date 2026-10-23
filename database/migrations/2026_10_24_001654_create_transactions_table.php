<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->date('traded_on');
            $table->string('type', 4);
            $table->string('currency_code', 3);
            $table->decimal('amount', 14, 2);
            $table->decimal('rate', 12, 2);
            $table->decimal('naira_total', 18, 2);
            $table->string('customer_name')->default('');
            $table->string('note')->default('');
            $table->string('receipt_path')->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestamps();

            $table->index(['is_public', 'traded_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
