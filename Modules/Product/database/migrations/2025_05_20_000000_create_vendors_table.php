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
        Schema::create('vendors', function (Blueprint $table) {
            // Primary key
            $table->uuid('id')->primary()->comment('Primary key UUID');

            // Vendor information
            $table->string('first_name')->comment('First name of the vendor');
            $table->string('last_name')->comment('Last name of the vendor');
            $table->string('company_name')->nullable()->comment('Company name of the vendor');
            $table->string('email')->unique()->comment('Email address of the vendor');
            $table->string('phone')->nullable()->comment('Phone number of the vendor');
            $table->text('address')->nullable()->comment('Street address of the vendor');
            $table->string('city')->nullable()->comment('City of the vendor');
            $table->string('state')->nullable()->comment('State of the vendor');
            $table->string('zip_code')->nullable()->comment('ZIP code of the vendor');
            $table->string('gstin')->nullable()->comment('GST Identification Number');

            // Timestamps and user tracking
            $table->uuid('created_by')->nullable()->comment('User who created the record');
            $table->uuid('last_updated_by')->nullable()->comment('User who last updated the record');

            // Indexes
            $table->index('first_name');
            $table->index('last_name');
            $table->index('company_name');
            $table->index('email');
            $table->index('phone');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};