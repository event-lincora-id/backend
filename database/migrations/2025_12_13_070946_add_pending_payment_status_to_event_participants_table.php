<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'pending_payment' to status enum
        DB::statement("ALTER TABLE event_participants MODIFY COLUMN status ENUM('registered', 'pending_payment', 'attended', 'cancelled', 'absent') DEFAULT 'registered'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'pending_payment' from status enum
        DB::statement("ALTER TABLE event_participants MODIFY COLUMN status ENUM('registered', 'attended', 'cancelled', 'absent') DEFAULT 'registered'");
    }
};
