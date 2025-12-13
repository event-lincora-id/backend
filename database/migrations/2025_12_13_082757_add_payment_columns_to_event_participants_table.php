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
            // Add payment tracking columns
            $table->enum('payment_status', ['pending', 'paid', 'expired', 'failed'])
                  ->nullable()
                  ->after('payment_reference');
            $table->string('payment_url')->nullable()->after('payment_status');
            $table->datetime('paid_at')->nullable()->after('payment_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_participants', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'payment_url', 'paid_at']);
        });
    }
};
