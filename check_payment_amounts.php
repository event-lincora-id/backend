<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CHECKING PAYMENT AMOUNTS ===" . PHP_EOL . PHP_EOL;

// Find the participant for "Startup Pitch Competition" with amount_paid = 100000
$paidParticipant = App\Models\EventParticipant::where('amount_paid', 100000)
    ->with('event')
    ->first();

if ($paidParticipant) {
    echo "Found participant with Rp 100,000 payment:" . PHP_EOL;
    echo "  User ID: {$paidParticipant->user_id}" . PHP_EOL;
    echo "  Event: {$paidParticipant->event->title}" . PHP_EOL;
    echo PHP_EOL;

    // Get all participants for this user
    $userParticipants = App\Models\EventParticipant::where('user_id', $paidParticipant->user_id)
        ->with('event')
        ->whereNotNull('payment_reference')
        ->orderBy('created_at', 'desc')
        ->get();

    echo "=== ALL PAYMENT HISTORY FOR THIS USER ===" . PHP_EOL;
    foreach ($userParticipants as $p) {
        echo "Event: {$p->event->title}" . PHP_EOL;
        echo "  Event Price: Rp " . number_format($p->event->price) . PHP_EOL;
        echo "  Amount Paid: Rp " . number_format($p->amount_paid ?? 0) . PHP_EOL;
        echo "  Payment Status: {$p->payment_status}" . PHP_EOL;
        echo "  Status: {$p->status}" . PHP_EOL;
        echo "  Is Paid: " . ($p->is_paid ? 'Yes' : 'No') . PHP_EOL;
        echo "  Payment Reference: {$p->payment_reference}" . PHP_EOL;
        echo "  Created: {$p->created_at}" . PHP_EOL;
        echo PHP_EOL;
    }

    echo "=== ANALYSIS ===" . PHP_EOL;
    foreach ($userParticipants as $p) {
        if ($p->is_paid && ($p->amount_paid == 0 || $p->amount_paid === null)) {
            echo "🚨 ISSUE: {$p->event->title}" . PHP_EOL;
            echo "   - Marked as PAID (is_paid=true)" . PHP_EOL;
            echo "   - But amount_paid = " . ($p->amount_paid ?? 'NULL') . PHP_EOL;
            echo "   - Event price is Rp " . number_format($p->event->price) . PHP_EOL;
            echo "   - Should be: amount_paid = {$p->event->price}" . PHP_EOL;
            echo PHP_EOL;
        }
    }
}
