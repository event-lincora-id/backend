<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\EventParticipant;
use App\Models\Notification;
use App\Models\Transaction;
use App\Services\BalanceService;
use Illuminate\Support\Facades\Log;

echo "=== Fixing Participant 133 Payment ===\n\n";

$participant = EventParticipant::find(133);

if (!$participant) {
    echo "Error: Participant 133 not found!\n";
    exit(1);
}

$event = $participant->event;
$organizer = $event->organizer;

echo "Participant ID: {$participant->id}\n";
echo "User: {$participant->user->full_name}\n";
echo "Event: {$event->title}\n";
echo "Organizer: {$organizer->full_name}\n";
echo "Amount Paid: Rp " . number_format($participant->amount_paid, 0, ',', '.') . "\n";
echo "Payment Status: {$participant->payment_status}\n\n";

if ($participant->payment_status !== 'paid') {
    echo "Error: Payment is not in 'paid' status. Current status: {$participant->payment_status}\n";
    exit(1);
}

// Check if payment already added to balance
$existingTransaction = Transaction::where('participant_id', $participant->id)
    ->where('type', 'payment_received')
    ->first();

if ($existingTransaction) {
    echo "⚠️  Payment already added to organizer balance!\n";
    echo "Transaction ID: {$existingTransaction->id}\n";
    echo "Amount: Rp " . number_format($existingTransaction->amount, 0, ',', '.') . "\n";
    exit(0);
}

echo "Processing payment...\n\n";

try {
    // 1. Add payment to organizer balance
    echo "1. Adding payment to organizer balance...\n";
    $balanceService = app(BalanceService::class);
    $balanceService->addPaymentToBalance($participant);
    echo "   ✅ Payment added to organizer balance\n\n";

    // 2. Create notification for organizer
    echo "2. Creating notification for organizer...\n";
    Notification::create([
        'user_id' => $event->user_id,
        'event_id' => $event->id,
        'type' => 'event_registration',
        'title' => 'New Paid Event Registration',
        'message' => $participant->user->full_name . ' has registered and paid for: ' . $event->title,
        'data' => [
            'participant_id' => $participant->id,
            'participant_name' => $participant->user->full_name,
            'amount_paid' => $participant->amount_paid
        ]
    ]);
    echo "   ✅ Notification created\n\n";

    // 3. Show updated balance
    echo "3. Updated Organizer Balance:\n";
    $organizerBalance = App\Models\OrganizerBalance::where('user_id', $organizer->id)->first();
    if ($organizerBalance) {
        echo "   Total Earned: Rp " . number_format($organizerBalance->total_earned, 0, ',', '.') . "\n";
        echo "   Available Balance: Rp " . number_format($organizerBalance->available_balance, 0, ',', '.') . "\n";
        echo "   Platform Fee Total: Rp " . number_format($organizerBalance->platform_fee_total, 0, ',', '.') . "\n";
    }

    echo "\n✅ Successfully processed participant 133 payment!\n";

} catch (\Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
