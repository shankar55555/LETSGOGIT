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
        Schema::create('purchase_bill_items', function (Blueprint $table) {
            // Primary key as UUID
            $table->uuid('id')->primary()->comment('Primary key UUID');

            // Foreign key to purchase_bills table
            $table->uuid('purchase_bill_id')->comment('Reference to purchase bill');

            // Item details
            $table->string('item_type')->default('inventory')->comment('Type of item: inventory or asset');
            $table->string('item_name')->comment('Name of the item');
            $table->uuid('variant_id')->nullable()->after('product_id')->comment('Reference to product variant for SKU selection');
            $table->uuid('account_id')->nullable()->comment('Reference to account (for inventory items)');
            $table->uuid('product_id')->nullable()->comment('Reference to product (for inventory items)');
            $table->string('sku')->nullable()->comment('Product SKU');
            $table->string('hsn_sac')->nullable()->comment('HSN/SAC code');
            $table->decimal('quantity', 12, 2)->default(1)->comment('Quantity of items');
            $table->decimal('rate', 12, 2)->default(0)->comment('Rate per unit');
            $table->decimal('discount', 12, 2)->default(0)->comment('Discount amount');
            $table->decimal('gst_percentage', 5, 2)->default(0)->comment('GST percentage');
            $table->decimal('amount', 12, 2)->default(0)->comment('Total amount for this item');

            // Audit fields
            $table->uuid('created_by')->comment('User who created the record');
            $table->uuid('last_updated_by')->nullable()->comment('User who last updated the record');

            // Timestamps and soft delete
            $table->softDeletes()->comment('Timestamp when record was deleted');
            $table->timestamps();

            // Indexes
            $table->index('purchase_bill_id');
            $table->index('variant_id');
            $table->index('item_type');
            $table->index('account_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_bill_items');
    }
};
