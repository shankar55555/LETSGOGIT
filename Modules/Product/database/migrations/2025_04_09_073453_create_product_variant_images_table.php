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
        Schema::create('product_variant_images', function (Blueprint $table) {
            // Primary key
            $table->uuid('id')->primary()->comment('Primary key UUID');

            // Foreign key to product_variants table
            $table->uuid('variant_id')->comment('Foreign key to product_variants table');

            // Image specific fields
            $table->longText('url')->comment('Image URL or path');
            $table->string('name')->nullable()->comment('Original image file name');
            $table->integer('sort_order')->default(0)->comment('Display order of images');
            $table->boolean('is_primary')->default(false)->comment('Whether this is the primary image');

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraint
            $table->foreign('variant_id')->references('id')->on('product_variants')->onDelete('cascade');

            // Indexes for performance
            $table->index('variant_id');
            $table->index(['variant_id', 'sort_order']);
            $table->index(['variant_id', 'is_primary']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variant_images');
    }
};