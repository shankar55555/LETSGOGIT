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


        try {
            Schema::table('notification_category', function (Blueprint $table) {
                $table->boolean('is_delete')->default(1)->after('category');
            });
        } catch (\Throwable $th) {
        }

        try {
            Schema::table('notification_types', function (Blueprint $table) {
                $table->boolean('is_delete')->default(1)->after('type_key');
            });
        } catch (\Throwable $th) {
        }

        try {
            Schema::table('notification_template_sections', function (Blueprint $table) {
                $table->string('title')->nullable()->after('is_enable');
            });
        } catch (\Throwable $th) {
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        try {
            Schema::table('notification_category', function (Blueprint $table) {
                $table->dropColumn('is_delete');
            });
        } catch (\Throwable $th) {
        }

        try {
            Schema::table('notification_types', function (Blueprint $table) {
                $table->dropColumn('is_delete');
            });
        } catch (\Throwable $th) {
        }

        try {
            Schema::table('notification_template_sections', function (Blueprint $table) {
                $table->dropColumn('title');
            });
        } catch (\Throwable $th) {
        }
    }
};
