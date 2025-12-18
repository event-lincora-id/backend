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
        Schema::table('event_participants', function (Blueprint $table) {
            $table->boolean('attendance_reminder_sent')->default(false)->after('attended_at');
            $table->boolean('attendance_reminder_final_sent')->default(false)->after('attendance_reminder_sent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_participants', function (Blueprint $table) {
            $table->dropColumn(['attendance_reminder_sent', 'attendance_reminder_final_sent']);
        });
    }
};
