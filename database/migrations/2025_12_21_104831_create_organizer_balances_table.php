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
        Schema::create('organizer_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('total_earned', 10, 2)->default(0);
            $table->decimal('withdrawn', 10, 2)->default(0);
            $table->decimal('pending_withdrawal', 10, 2)->default(0);
            $table->decimal('available_balance', 10, 2)->default(0);
            $table->decimal('platform_fee_total', 10, 2)->default(0);
            $table->timestamps();

            // Add unique index on user_id
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizer_balances');
    }
};
