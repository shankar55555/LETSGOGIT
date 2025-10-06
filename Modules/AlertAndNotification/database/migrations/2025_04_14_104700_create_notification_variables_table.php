<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationVariablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notification_variables', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('notification_type_id')->nullable();
            $table->string('variables')->nullable();
            $table->string('enable')->nullable()->default('Enable')->comment('Enable,Disable');
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
        Schema::dropIfExists('emails_types_variables');
    }
}
