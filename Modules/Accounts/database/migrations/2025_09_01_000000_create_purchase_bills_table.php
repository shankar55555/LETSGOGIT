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
        Schema::create('purchase_bills', function (Blueprint $table) {
            // Primary key as UUID
            $table->uuid('id')->primary()->comment('Primary key UUID');

            // Bill information
            $table->string('bill_number')->comment('Purchase bill number');
            $table->date('bill_date')->comment('Date of the bill');
            $table->date('due_date')->nullable()->comment('Due date for payment');

            // Vendor information
            $table->uuid('vendor_id')->nullable()->comment('Reference to vendor/supplier');

            // Purchase details
            $table->string('purchase_mode')->default('inventory')->comment('Type of purchase: inventory, asset, or both');
            $table->json('items')->nullable()->comment('Bill line items in JSON format');
            $table->text('notes')->nullable()->comment('Additional notes about the purchase');

            // Financial information
            $table->decimal('sub_total', 12, 2)->default(0)->comment('Amount before taxes');
            $table->decimal('tax_amount', 12, 2)->default(0)->comment('Total tax amount');
            $table->decimal('total_amount', 12, 2)->default(0)->comment('Final total amount');

            $table->string('vendor_state')->nullable()->after('vendor_id')->comment('State of the vendor for tax calculation');

            // Add payment related fields
            $table->string('payment_mode')->nullable()->after('status')->comment('Mode of payment');
            $table->decimal('discount_amount', 12, 2)->default(0)->after('tax_amount')->comment('Discount amount');

            // Add GST breakdown fields
            $table->decimal('cgst_amount', 12, 2)->default(0)->after('discount_amount')->comment('CGST amount');
            $table->decimal('sgst_amount', 12, 2)->default(0)->after('cgst_amount')->comment('SGST amount');
            $table->decimal('igst_amount', 12, 2)->default(0)->after('sgst_amount')->comment('IGST amount');

            // Add bill image field
            $table->longText('bill_image')->nullable()->after('notes')->comment('Path to uploaded bill image');
            $table->string('status')->default('unpaid')->comment('Payment status of the bill');

            // Audit fields
            $table->uuid('created_by')->comment('User who created the record');
            $table->uuid('last_updated_by')->nullable()->comment('User who last updated the record');

            // Timestamps and soft delete
            $table->softDeletes()->comment('Timestamp when record was deleted');
            $table->timestamps();

            // Indexes
            $table->index('bill_number');
            $table->index('bill_date');
            $table->index('vendor_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_bills');
    }
};
