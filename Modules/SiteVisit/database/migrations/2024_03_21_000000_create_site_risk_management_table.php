<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('site_risk_management', function (Blueprint $table) {
            // Primary key as UUID
            $table->uuid('id')->primary()->comment('Primary key using UUID format');
            $table->uuid('site_visit_id');
            $table->string('customer_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address');
            $table->string('building_type');
            $table->string('roof_type');
            $table->string('height_of_roof')->nullable();
            $table->string('service');
            $table->datetime('visit_datetime');
            $table->text('solution_recommended');
            $table->uuid('visit_assignee_id')->nullable();
            $table->uuid('created_by')->comment('Identifier of who created this record');
            $table->uuid('last_updated_by')->nullable()->comment('User who last updated this record');
            $table->softDeletes()->comment('Timestamp when record was deleted');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('site_risk_management');
    }
};
