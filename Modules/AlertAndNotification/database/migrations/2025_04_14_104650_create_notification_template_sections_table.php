<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationTemplateSectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notification_template_sections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('notification_type_id')->nullable();
            $table->string('title')->nullable();
            $table->string('email_subject')->nullable();
            $table->longText('email_body')->nullable();
            $table->longText('whats_app_message')->nullable();
            $table->longText('sms_message')->nullable();
            $table->longText('bell_notification_message')->nullable();
            $table->longText('app_message')->nullable();
            $table->text('hidden_pre_header')->nullable();
            $table->string('priority')->comment('Critical,Medium,High,Low');
            $table->string('is_enable')->default('Enable')->comment("Required, Disable, Enable");
            $table->foreign('notification_type_id')->references('id')->on('notification_types')->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notification_template_sections');
    }
}
