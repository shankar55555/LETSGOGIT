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
        Schema::create('products', function (Blueprint $table) {
            // Primary key
            $table->uuid('id')->primary()->comment('Primary key UUID');

            // Core Information
            $table->string('name')->comment('Name of the product or service');
            $table->string('purchase_no')->unique()->comment('Auto-generated purchase number with prefix');
            $table->string('category')->nullable()->after('purchase_no')->comment('Product category');
            $table->string('collection')->nullable()->after('category')->comment('Product collection');

            // Product Details
            $table->string('material_fabric')->nullable()->after('collection')->comment('Material or fabric');
            $table->text('care_instruction')->nullable()->after('material_fabric')->comment('Care instructions');
            $table->string('season')->nullable()->after('care_instruction')->comment('Season');

            // Media & Branding
            $table->json('tags')->nullable()->after('season')->comment('Product tags');
            $table->string('status')->default('active')->after('tags')->comment('Product status');
            $table->text('short_description')->nullable()->after('status')->comment('Short description');
            $table->text('detail_description')->nullable()->after('short_description')->comment('Detailed description');

            // Timestamps and user tracking
            $table->uuid('created_by')->nullable()->comment('User who created the record');
            $table->uuid('last_updated_by')->nullable()->comment('User who last updated the record');

            // Indexes
            $table->index('name');
            $table->index('purchase_no');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product');
    }
};
