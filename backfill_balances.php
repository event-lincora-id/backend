<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\EventParticipant;
use App\Services\BalanceService;
use Illuminate\Support\Facades\DB;

echo "=== BACKFILL ORGANIZER BALANCES ===\n\n";

// Get all paid participants
$paidParticipants = EventParticipant::whereNotNull('amount_paid')
    ->where('amount_paid', '>', 0)
    ->with(['event', 'event.organizer'])
    ->orderBy('paid_at', 'asc')
    ->get();

echo "Found {$paidParticipants->count()} paid participants to process\n";
echo "Starting backfill...\n\n";

$balanceService = new BalanceService();
$processed = 0;
$errors = 0;
$organizerStats = [];

foreach ($paidParticipants as $participant) {
    try {
        DB::beginTransaction();

        // Add payment to balance
        $balanceService->addPaymentToBalance($participant);

        // Track stats per organizer
        $organizerId = $participant->event->user_id;
        if (!isset($organizerStats[$organizerId])) {
            $organizerStats[$organizerId] = [
                'name' => $participant->event->organizer->name,
                'count' => 0,
                'total' => 0,
            ];
        }
        $organizerStats[$organizerId]['count']++;
        $organizerStats[$organizerId]['total'] += $participant->amount_paid;

        DB::commit();
        $processed++;

        if ($processed % 10 == 0) {
            echo "Processed {$processed}/{$paidParticipants->count()} participants...\n";
        }

    } catch (\Exception $e) {
        DB::rollBack();
        $errors++;
        echo "❌ Error processing participant {$participant->id}: {$e->getMessage()}\n";
    }
}

echo "\n=== BACKFILL COMPLETE ===\n";
echo "✅ Successfully processed: {$processed}\n";
echo "❌ Errors: {$errors}\n\n";

echo "=== ORGANIZER SUMMARY ===\n";
foreach ($organizerStats as $organizerId => $stats) {
    $balance = \App\Models\OrganizerBalance::where('user_id', $organizerId)->first();
    echo "\nOrganizer: {$stats['name']} (ID: {$organizerId})\n";
    echo "  Payments Processed: {$stats['count']}\n";
    echo "  Total Revenue: Rp " . number_format($stats['total'], 0, ',', '.') . "\n";
    if ($balance) {
        echo "  Available Balance: Rp " . number_format($balance->available_balance, 0, ',', '.') . "\n";
        echo "  Platform Fee Deducted: Rp " . number_format($balance->platform_fee_total, 0, ',', '.') . "\n";
    }
}

echo "\n✅ Backfill completed successfully!\n";
