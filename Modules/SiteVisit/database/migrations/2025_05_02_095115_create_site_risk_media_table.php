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
        Schema::create('site_risk_media', function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('Primary key using UUID format');
            $table->string('type')->comment('image/video');
            $table->string('filename');
            $table->text('path');
            $table->uuid('site_visit_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_risk_media');
    }
};
