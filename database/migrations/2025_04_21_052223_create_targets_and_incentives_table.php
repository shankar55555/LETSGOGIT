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
        Schema::create('targets_and_incentives', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->integer('target_amount');
            $table->integer('achieved_amount')->nullable();
            $table->integer('incentive_percentage')->comment('By admin allowed apply percentage');
            $table->decimal('incentive', 10, 2)->nullable()->comment('User Incentive');
            $table->date('month'); // Stores year-month (e.g., 2023-10-01)
            $table->boolean('is_paid')->default(false);
            $table->timestamps();
            // Indexes
            $table->index('target_amount');
            $table->index('incentive');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('targets_and_incentives');
    }
};
