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
        Schema::create('peak4_hours', function (Blueprint $table) {
            $table->id();
            $table->string('symbol');
            $table->decimal('price', 20, 10);
            $table->decimal('hit_20', 20, 10);
            $table->date('hit_time_20')->nullable();
            $table->boolean('status_20');
            $table->decimal('hit_25', 20, 10);
            $table->date('hit_time_25')->nullable();
            $table->decimal('target_20', 20, 10)->nullable();
            $table->date('target_time_20')->nullable();
            $table->decimal('target_50', 20, 10)->nullable();
            $table->date('target_time_50')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peak4_hours');
    }
};
