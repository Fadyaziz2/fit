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
            $table->decimal('unit_price', 10, 2)->default(0)->after('quantity');
            $table->decimal('total_price', 10, 2)->default(0)->after('unit_price');
            $table->string('payment_method')->default('cash_on_delivery')->after('total_price');
            $table->text('status_comment')->nullable()->after('status');
            $table->string('customer_name')->nullable()->after('status_comment');
            $table->string('customer_phone')->nullable()->after('customer_name');
            $table->text('shipping_address')->nullable()->after('customer_phone');
            $table->text('customer_note')->nullable()->after('shipping_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_orders', function (Blueprint $table) {
            $table->dropColumn([
                'unit_price',
                'total_price',
                'payment_method',
                'status_comment',
                'customer_name',
                'customer_phone',
                'shipping_address',
                'customer_note',
            ]);
        });
    }
};
