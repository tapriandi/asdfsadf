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
        Schema::create('histories', function (Blueprint $table) {
            $table->id();
            $table->string('symbol');
            $table->string('exchange');
            $table->string('timeframe');
            $table->decimal('volume', 30, 20);
            $table->decimal('price', 30, 20);
            $table->decimal('price_high', 30, 20);
            $table->decimal('price_close', 30, 20)->nullable();
            $table->date('time_price_close', 30, 20)->nullable();
            $table->decimal('price_hit_20', 30, 20)->nullable();
            $table->decimal('price_hit_25', 30, 20)->nullable();
            $table->date('time_hit_20')->nullable();
            $table->date('time_hit_25')->nullable();
            $table->date('time_sell_1')->nullable();
            $table->date('time_sell_2')->nullable();
            $table->date('time_hit_target_10')->nullable();
            $table->date('time_hit_target_20')->nullable();
            $table->date('time_hit_target_30')->nullable();
            $table->date('time_hit_target_40')->nullable();
            $table->date('time_hit_target_50')->nullable();
            $table->timestamps();
        });
    }



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('histories');
    }
};
