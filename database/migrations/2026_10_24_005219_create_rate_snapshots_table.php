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
        Schema::create('rate_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('currency_code', 3);
            $table->date('recorded_on');
            $table->decimal('buy_rate', 12, 2)->nullable();
            $table->decimal('sell_rate', 12, 2)->nullable();
            $table->timestamps();

            $table->unique(['currency_code', 'recorded_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rate_snapshots');
    }
};
