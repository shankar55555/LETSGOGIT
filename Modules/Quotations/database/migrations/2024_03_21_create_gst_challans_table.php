<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('gst_challans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('cpin')->unique();
            $table->string('challan_number')->nullable();
            $table->string('gstin');
            $table->string('payment_type');
            $table->string('financial_year');
            $table->string('tax_period');
            $table->json('tax_items');
            $table->decimal('total_amount', 10, 2);
            $table->date('challan_date');
            $table->string('status')->default('generated');
            $table->uuid('created_by')->nullable();
            $table->uuid('last_updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('created_by')->references('uuid')->on('users')->onDelete('set null');
            $table->foreign('last_updated_by')->references('uuid')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('gst_challans');
    }
}; 
