<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('site_visits', function (Blueprint $table) {
            // Primary key as UUID
            $table->uuid('id')->primary()->comment('Primary key using UUID format');
            $table->enum('visit_type', ['inspection', 'installation', 'other'])->default('other')->nullable();
            $table->timestamp('visit_time')->comment('Scheduled date and time of the site visit');
            $table->uuid('visit_assignee')->nullable()->comment('User ID of the assigned staff member');
            $table->string('status')->comment('Current status of the site visit');
            $table->text('visit_notes')->nullable()->comment('Additional notes about the visit');
            $table->text('products')->nullable()->comment('Product/service uuids');
            $table->uuid('lead_id')->nullable()->comment('Associated lead ID if applicable');
            $table->uuid('client_id')->nullable()->comment('Associated client ID if applicable');
            $table->uuid('created_by')->comment('Identifier of who created this record');
            $table->uuid('last_updated_by')->nullable()->comment('User who last updated this record');
            $table->softDeletes()->comment('Timestamp when record was deleted');
            $table->timestamps();

            // Indexes for better performance
            $table->index(['lead_id', 'client_id', 'visit_assignee']);
        });
    }

    public function down()
    {

        Schema::table('site_visits', function (Blueprint $table) {
            $table->dropSoftDeletes(); // Drop `deleted_at` column
        });



        Schema::dropIfExists('site_visits');
    }
};
