<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\User;
use App\Models\OrganizerBalance;
use App\Models\WithdrawalRequest;

echo "=== DATABASE CHECK ===\n\n";

// Check for paid events
$paidEvents = Event::where('price', '>', 0)->count();
echo "Paid Events (price > 0): {$paidEvents}\n";

// Check for participants with payment
$paidParticipants = EventParticipant::whereNotNull('amount_paid')
    ->where('amount_paid', '>', 0)
    ->count();
echo "Participants with amount_paid > 0: {$paidParticipants}\n";

// Check for any is_paid = true
$isPaidTrue = EventParticipant::where('is_paid', true)->count();
echo "Participants with is_paid = true: {$isPaidTrue}\n";

// Get sample paid participant
$samplePaid = EventParticipant::where('is_paid', true)
    ->whereNotNull('amount_paid')
    ->where('amount_paid', '>', 0)
    ->with(['event', 'event.organizer'])
    ->first();

if ($samplePaid) {
    echo "\n=== SAMPLE PAID PARTICIPANT ===\n";
    echo "Participant ID: {$samplePaid->id}\n";
    echo "Event: {$samplePaid->event->title}\n";
    echo "Organizer: {$samplePaid->event->organizer->name} (ID: {$samplePaid->event->organizer->id})\n";
    echo "Amount Paid: Rp " . number_format($samplePaid->amount_paid, 0, ',', '.') . "\n";
    echo "Payment Status: {$samplePaid->payment_status}\n";

    // Check if balance exists
    $balance = OrganizerBalance::where('user_id', $samplePaid->event->user_id)->first();
    if ($balance) {
        echo "\n=== ORGANIZER BALANCE ===\n";
        echo "Total Earned: Rp " . number_format($balance->total_earned, 0, ',', '.') . "\n";
        echo "Available Balance: Rp " . number_format($balance->available_balance, 0, ',', '.') . "\n";
        echo "Withdrawn: Rp " . number_format($balance->withdrawn, 0, ',', '.') . "\n";
        echo "Pending Withdrawal: Rp " . number_format($balance->pending_withdrawal, 0, ',', '.') . "\n";
        echo "Platform Fee Total: Rp " . number_format($balance->platform_fee_total, 0, ',', '.') . "\n";
    } else {
        echo "\n❌ No balance record found for organizer\n";
        echo "   Balance will be created when next payment is processed\n";
    }
} else {
    echo "\n❌ No paid participants with amount_paid > 0 found\n";
    echo "   This means the new balance system hasn't processed any payments yet\n";
}

// Check organizer balances
echo "\n=== ORGANIZER BALANCES ===\n";
$balances = OrganizerBalance::with('organizer')->get();
echo "Total Balance Records: {$balances->count()}\n";

foreach ($balances as $balance) {
    echo "\nOrganizer: {$balance->organizer->name} (ID: {$balance->user_id})\n";
    echo "  Available Balance: Rp " . number_format($balance->available_balance, 0, ',', '.') . "\n";
}

// Check withdrawal requests
echo "\n=== WITHDRAWAL REQUESTS ===\n";
$withdrawals = WithdrawalRequest::with('organizer')->get();
echo "Total Withdrawal Requests: {$withdrawals->count()}\n";

foreach ($withdrawals as $wd) {
    echo "\nRequest ID: {$wd->id}\n";
    echo "  Organizer: {$wd->organizer->name}\n";
    echo "  Amount: Rp " . number_format($wd->amount, 0, ',', '.') . "\n";
    echo "  Status: {$wd->status}\n";
}

echo "\n=== RECOMMENDATION ===\n";
if ($paidParticipants > 0) {
    echo "✅ You have existing paid data\n";
    echo "   - The balance system will be updated when new payments come in\n";
    echo "   - You can simulate a new payment to test the system\n";
} else {
    echo "⚠️  No paid participants with actual payment amounts found\n";
    echo "   - You need to create a test payment to populate the balance system\n";
    echo "   - Or wait for a real payment through Xendit webhook\n";
}

echo "\n";
