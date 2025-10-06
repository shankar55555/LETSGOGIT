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
        Schema::create('b_to_b_users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('company')->nullable();
            $table->string('country_code')->default('+91');
            $table->string('email');
            $table->string('contact_no');
            $table->text('address')->nullable();
            $table->string('role')->nullable();
            $table->text('avatar')->nullable();
            $table->string('status')->default('active')->comment('active', 'in-active');
            $table->uuid('created_by')->nullable();
            $table->uuid('last_updated_by')->nullable();

            $table->unique(['name', 'email', 'contact_no']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('b_to_b_users');
    }
};
