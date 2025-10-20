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
        Schema::create('user_body_compositions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('recorded_at');
            $table->decimal('fat_weight', 8, 2)->nullable();
            $table->decimal('water_weight', 8, 2)->nullable();
            $table->decimal('muscle_weight', 8, 2)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_body_compositions');
    }
};
