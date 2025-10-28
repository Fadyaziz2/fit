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
            $table->date('start_date')->nullable()->after('serve_times');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assign_diets', function (Blueprint $table) {
            $table->dropColumn('start_date');
        });
    }
};
