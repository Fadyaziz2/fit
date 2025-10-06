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
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->foreignId('specialist_id')->nullable()->after('user_id')->constrained('specialists')->nullOnDelete();
            $table->timestamp('free_booking_used_at')->nullable()->after('specialist_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('specialist_id');
            $table->dropColumn('free_booking_used_at');
        });
    }
};
