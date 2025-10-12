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
        Schema::table('product_orders', function (Blueprint $table) {
            $table->decimal('subtotal_price', 10, 2)->default(0)->after('unit_price');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('subtotal_price');
            $table->string('discount_code')->nullable()->after('discount_amount');
            $table->foreignId('discount_code_id')->nullable()->after('discount_code')->constrained('discount_codes')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_orders', function (Blueprint $table) {
            $table->dropForeign(['discount_code_id']);
            $table->dropColumn(['subtotal_price', 'discount_amount', 'discount_code', 'discount_code_id']);
        });
    }
};
