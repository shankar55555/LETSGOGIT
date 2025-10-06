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
            Schema::table('admin_control_configs', function (Blueprint $table) {
                $table->string('trigger_action')->nullable()->after('is_predefined');
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
            Schema::table('admin_control_configs', function (Blueprint $table) {
                $table->dropColumn("trigger_action");
            });
        } catch (\Throwable $th) {
        }
    }
};
