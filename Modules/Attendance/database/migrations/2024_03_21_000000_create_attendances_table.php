<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');

            // For multiple entries per day
            $table->date('attendance_date'); // Separate date for easier daily filtering
            $table->time('login_time')->nullable();
            $table->time('logout_time')->nullable();

            // Session tracking
            $table->string('session_token')->unique(); // Unique identifier for each session
            $table->json('device_info')->nullable();
            // Additional metadata
            $table->boolean('is_manual')->default(false); // Was this entry manually added?
            
            // Optimized indexes
            $table->index(['attendance_date']); // Faster daily lookups
            $table->softDeletes(); // Adds a `deleted_at` column
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('attendances');
    }
};
