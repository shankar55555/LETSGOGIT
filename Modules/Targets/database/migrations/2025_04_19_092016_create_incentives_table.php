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
        Schema::create('incentives', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->uuid('target_id');
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('pending')->comment('pending, approved, paid'); // pending, approved, paid
            $table->date('payment_date')->nullable();
            $table->text('notes')->nullable();
            // Indexes
            $table->index('status');
            $table->softDeletes(); // Adds a `deleted_at` column
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incentives');
    }
};
