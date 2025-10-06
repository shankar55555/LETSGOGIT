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
        Schema::create('rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('rule');
            $table->string('rule_slug');
            $table->string('condition_type')->nullable()->comment('OR/AND');
            $table->json('conditions')->nullable();
            $table->json('actions')->nullable();
            $table->string('status')->default('active')->comment('active, in-active, draft');
            $table->uuid('created_by')->nullable();
            $table->uuid('last_updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rules');
    }
};
