<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema, DB};

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            // Primary UUID with comment
            $table->uuid('id')->primary()->comment('Auto-generated UUID primary key');
            // Nullable foreign key with constraint
            $table->uuid('lead_id')->nullable()->comment('Reference to parent lead if exists');
            // Client information
            $table->string('name')->index();
            $table->string('type')->index();
            $table->string('contact_person')->nullable();
            $table->string('contact_person_role')->nullable();
            $table->string('gst')->nullable();
            // Contact information with unique constraint
            $table->string('email')->index()->nullable();
            $table->string('country_code')->default('91');
            $table->string('phone')->nullable();
            $table->json('secondary_phone')->nullable()->comment('Secondary Phone number of the client as json');
            $table->text('avatar')->nullable();
            // Status fields
            $table->string('status')->index()->default('active')->comment('Current client status');
            $table->uuid('assigned_user')->nullable()->comment('User responsible for this client');
            // Audit tracking
            $table->uuid('created_by')
                ->comment('User who created the record');
            $table->uuid('last_updated_by')
                ->nullable()
                ->comment('User who last modified the record');

            $table->enum('converted_by', ['manual', 'auto'])->nullable()->comment('Indicates if client was converted manually or automatically');

            $table->uuid('city_id')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->date('anniversary_date')->nullable();


            $table->softDeletes(); // Adds a `deleted_at` column
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
