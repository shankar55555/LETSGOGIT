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
        Schema::create('product_variants', function (Blueprint $table) {
            // Primary key
            $table->uuid('id')->primary()->comment('Primary key UUID');

            // Foreign key to products table
            $table->uuid('product_id')->comment('Foreign key to products table');

            // Variant specific fields
            $table->string('sku')->unique()->comment('Stock Keeping Unit');
            $table->decimal('mrp', 10, 2)->comment('Maximum Retail Price');
            $table->integer('stock_quantity')->default(0)->comment('Current stock quantity');
            $table->integer('low_stock_alert')->default(5)->comment('Low stock alert threshold');

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraint
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');

            // Indexes for performance
            $table->index('product_id');
            $table->index('sku');
            $table->index('stock_quantity');
            $table->index(['product_id', 'sku']); // Composite index for filtering
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
