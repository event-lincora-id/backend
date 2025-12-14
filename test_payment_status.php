<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TESTING PAYMENT STATUS VERIFICATION BUG ===" . PHP_EOL . PHP_EOL;

// Find participants with pending_payment status
$pending = App\Models\EventParticipant::where('status', 'pending_payment')
    ->with('event')
    ->get();

echo "=== PENDING PAYMENT PARTICIPANTS ===" . PHP_EOL;
foreach ($pending as $p) {
    echo "ID: {$p->id} | Event: {$p->event->title}" . PHP_EOL;
    echo "  Status: {$p->status} | Payment Status: {$p->payment_status} | Is Paid: " . ($p->is_paid ? 'Yes' : 'No') . PHP_EOL;
    if ($p->payment_reference) {
        echo "  Payment Reference: {$p->payment_reference}" . PHP_EOL;
    }
    if ($p->is_paid && $p->status === 'pending_payment') {
        echo "  🚨 BUG DETECTED: User PAID but status still 'pending_payment'!" . PHP_EOL;
        echo "      - This means webhook didn't arrive OR getPaymentStatus() was called but didn't update status field" . PHP_EOL;
        echo "      - registered_count was NOT incremented (correct: shouldn't increment without payment)" . PHP_EOL;
        echo "      - BUT now payment IS confirmed, so status should be 'registered' and count should increment!" . PHP_EOL;
    }
    echo PHP_EOL;
}
echo "Total pending: " . $pending->count() . PHP_EOL . PHP_EOL;

// Check for the specific bug: is_paid=true but status='pending_payment'
$buggedParticipants = App\Models\EventParticipant::where('status', 'pending_payment')
    ->where('is_paid', true)
    ->with('event')
    ->get();

echo "=== CONFIRMED BUG: Paid but Status Not Updated ===" . PHP_EOL;
if ($buggedParticipants->count() > 0) {
    echo "Found {$buggedParticipants->count()} participant(s) who PAID but are stuck in 'pending_payment' status:" . PHP_EOL;
    foreach ($buggedParticipants as $p) {
        echo "  - Participant ID: {$p->id} | Event: {$p->event->title}" . PHP_EOL;
        echo "    Payment Status: {$p->payment_status} | Paid At: {$p->paid_at}" . PHP_EOL;
        echo "    Event registered_count: {$p->event->registered_count} (should be +1 for this participant)" . PHP_EOL;
    }
} else {
    echo "No bugged participants found (either no payments yet, or webhooks are working perfectly)" . PHP_EOL;
}
echo PHP_EOL;

// Show event registered counts vs actual paid participants
echo "=== EVENT REGISTRATION ACCURACY CHECK ===" . PHP_EOL;
$paidEvents = App\Models\Event::where('price', '>', 0)->get();
foreach ($paidEvents as $event) {
    // Count participants who are truly registered (status='registered')
    $actualRegistered = App\Models\EventParticipant::where('event_id', $event->id)
        ->where('status', 'registered')
        ->count();

    // Count participants who paid but are stuck in pending_payment
    $paidButStuck = App\Models\EventParticipant::where('event_id', $event->id)
        ->where('status', 'pending_payment')
        ->where('is_paid', true)
        ->count();

    // Count participants still waiting for payment
    $trulyPending = App\Models\EventParticipant::where('event_id', $event->id)
        ->where('status', 'pending_payment')
        ->where('is_paid', false)
        ->count();

    echo "Event: {$event->title} (ID: {$event->id}) | Price: Rp {$event->price}" . PHP_EOL;
    echo "  registered_count in DB: {$event->registered_count}" . PHP_EOL;
    echo "  Actual status='registered': {$actualRegistered}" . PHP_EOL;
    echo "  Paid but stuck (is_paid=true, status='pending_payment'): {$paidButStuck}" . PHP_EOL;
    echo "  Truly pending (is_paid=false): {$trulyPending}" . PHP_EOL;

    $shouldBe = $actualRegistered + $paidButStuck;
    if ($event->registered_count != $shouldBe) {
        echo "  ❌ MISMATCH: registered_count should be {$shouldBe} but is {$event->registered_count}" . PHP_EOL;
    } else if ($paidButStuck > 0) {
        echo "  ⚠️ WARNING: {$paidButStuck} paid users not counted because status not updated" . PHP_EOL;
    } else {
        echo "  ✅ Count is accurate" . PHP_EOL;
    }
    echo PHP_EOL;
}
