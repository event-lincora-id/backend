<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Event 26 Details ===\n";
$event = App\Models\Event::find(26);
if ($event) {
    echo "Event ID: " . $event->id . "\n";
    echo "Title: " . $event->title . "\n";
    echo "User ID (Organizer): " . ($event->user_id ?? 'NULL') . "\n";
    echo "Price: " . $event->price . "\n";
    echo "Registered count: " . $event->registered_count . "\n";
    echo "Max participants: " . ($event->quota ?? 'NULL') . "\n";
    echo "Quota Full Notified: " . ($event->quota_full_notified ? 'YES ✅' : 'NO ❌') . "\n";
    echo "Is Full: " . ($event->quota && $event->registered_count >= $event->quota ? 'YES' : 'NO') . "\n";

    // Try to get organizer details
    try {
        $organizer = $event->organizer;
        if ($organizer) {
            echo "Organizer Name: " . $organizer->full_name . "\n";
            echo "Organizer Email: " . $organizer->email . "\n";
        } else {
            echo "Organizer: NULL (Event has no organizer!)\n";
        }
    } catch (Exception $e) {
        echo "Error getting organizer: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Participant 133 Details ===\n";
$participant = App\Models\EventParticipant::find(133);
if ($participant) {
    echo "User ID: " . $participant->user_id . "\n";
    echo "Event ID: " . $participant->event_id . "\n";
    echo "Payment Status: " . $participant->payment_status . "\n";
    echo "Payment Reference: " . $participant->payment_reference . "\n";
}

echo "\n=== Organizer Balance (user_id=28) ===\n";
$organizerBalance = App\Models\OrganizerBalance::where('user_id', 28)->first();
if ($organizerBalance) {
    echo "Organizer Balance ID: " . $organizerBalance->id . "\n";
    echo "User ID: " . $organizerBalance->user_id . "\n";
    echo "Total Earned: " . $organizerBalance->total_earned . "\n";
    echo "Available Balance: " . $organizerBalance->available_balance . "\n";
    echo "Withdrawn: " . $organizerBalance->withdrawn . "\n";
    echo "Platform Fee Total: " . $organizerBalance->platform_fee_total . "\n";

    // Check transactions for this organizer
    $transactions = App\Models\Transaction::where('user_id', 28)->get();
    echo "\nTotal transactions count: " . $transactions->count() . "\n";

    // Check for event 26 specifically
    $event26Transactions = App\Models\Transaction::where('user_id', 28)
        ->where('event_id', 26)
        ->get();
    echo "Transactions for event 26: " . $event26Transactions->count() . "\n";
} else {
    echo "No organizer balance found for user 28\n";
}

echo "\n=== All Participants for Event 26 ===\n";
$allParticipants = App\Models\EventParticipant::where('event_id', 26)->get();
foreach ($allParticipants as $p) {
    echo "ID: {$p->id}, User: {$p->user_id}, Status: {$p->payment_status}\n";
}
