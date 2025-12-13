<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Event;
use App\Models\EventParticipant;

echo "Checking Event QR Codes...\n\n";

$qrCodes = [
    'event_1765625302_693d4dd61535c',
    'event_1765594448_693cd5508b3e9'
];

$user = User::where('email', 'lkings013@gmail.com')->first();

if (!$user) {
    echo "❌ User not found!\n";
    exit(1);
}

echo "✅ User: {$user->full_name} (ID: {$user->id})\n\n";

echo "═══════════════════════════════════════════════════════\n\n";

foreach ($qrCodes as $qrCode) {
    echo "Checking QR Code: {$qrCode}\n";
    echo "─────────────────────────────────────────────────────────\n";

    // Find event by QR code
    $event = Event::where('qr_code_string', $qrCode)->first();

    if (!$event) {
        echo "❌ Event NOT found in Backend database\n";
        echo "   This QR code doesn't match any event\n";
        echo "   Possible reasons:\n";
        echo "   - Event only exists in frontend database (port 8002)\n";
        echo "   - Event doesn't have qr_code_string set\n";
        echo "   - QR code is incorrect\n\n";
        continue;
    }

    echo "✅ Event found:\n";
    echo "   ID: {$event->id}\n";
    echo "   Title: {$event->title}\n";
    echo "   Organizer ID: {$event->user_id}\n";
    echo "   QR Code String: {$event->qr_code_string}\n";

    // Check if user is registered
    $participant = EventParticipant::where('user_id', $user->id)
        ->where('event_id', $event->id)
        ->first();

    if (!$participant) {
        echo "\n❌ User NOT registered for this event\n";
        echo "   To fix: Register user for this event\n\n";
    } else {
        echo "\n✅ User IS registered:\n";
        echo "   Participant ID: {$participant->id}\n";
        echo "   Status: {$participant->status}\n";
        echo "   Attended At: " . ($participant->attended_at ?? 'NULL') . "\n\n";
    }

    echo "═══════════════════════════════════════════════════════\n\n";
}

// Also show all events in Backend
echo "All Events in Backend Database:\n";
echo "─────────────────────────────────────────────────────────\n";
$allEvents = Event::select('id', 'title', 'user_id', 'qr_code_string')->get();

if ($allEvents->isEmpty()) {
    echo "❌ No events found in Backend database\n";
} else {
    foreach ($allEvents as $event) {
        echo "ID: {$event->id} | Title: {$event->title} | QR: " . ($event->qr_code_string ?? 'NULL') . "\n";
    }
}

echo "\n";
