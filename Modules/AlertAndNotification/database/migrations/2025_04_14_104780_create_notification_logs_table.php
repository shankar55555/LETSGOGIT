<?php

use App\Constants\CommonConst;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('receiver_contact'); # Can store email or phone number
            $table->string('section_type')->nullable();
            $table->string('subject');
            $table->longText('content');
            $table->string('priority')->comment('Critical,Medium,High,Low');
            $table->string('status')->default('pending')->comment('pending, success, failed');
            $table->longText('message')->nullable();
            $table->longText('email_body')->nullable();
            $table->longText('additional_info')->nullable();
            $table->uuid('receiver_id')->nullable(); # users ,b2b ,client ,lead users id save
            $table->uuid('sender_id')->nullable(); # users table uuid
            $table->uuid('notification_type_id')->nullable(); # notification_types this table id
            $table->uuid('module_id')->nullable(); # Lead,Client, any Action id  
            $table->boolean('is_delete')->default(0);
            $table->json('showing_user_ids')->nullable();
            $table->boolean('is_notification')->default(0)->comment('0->Email,Sms,Bell Notification,WhatsApp,App, 1->only Notification');
            $table->foreign('sender_id')->references('uuid')->on('users')->onUpdate('cascade')->onDelete('cascade');
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
        Schema::dropIfExists('notification_logs');
    }
}
