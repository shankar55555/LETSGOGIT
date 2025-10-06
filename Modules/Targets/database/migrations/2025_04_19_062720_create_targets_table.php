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

        Schema::create('targets', function (Blueprint $table) {
            // Primary key as UUID
            $table->uuid('id')->primary()->comment('Primary key UUID');

            // Target information
            $table->string('title');
            $table->string('target_type')->comment('Daily, Weekly, Monthly');
            $table->integer('target_value')->comment('number of leads');
            $table->decimal('target_amount', 12, 2)->nullable()->comment('Total target amount');
            $table->decimal('incentive_percent', 5, 2)->nullable()->comment('Incentive percentage');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            // Timestamps
            $table->uuid('created_by')->nullable()->comment('User who created the target');
            $table->uuid('last_updated_by')->nullable()->comment('User who last updated the target');

            // Indexes
            $table->index('title');
            $table->softDeletes(); // Adds a `deleted_at` column
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('targets');
    }
};
