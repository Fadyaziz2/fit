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
        Schema::table('assign_diets', function (Blueprint $table) {
            $table->json('serve_times')->nullable()->after('diet_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assign_diets', function (Blueprint $table) {
            $table->dropColumn('serve_times');
        });
    }
};
