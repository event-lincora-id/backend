<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Event;
use App\Models\EventParticipant;

echo "Registering user for Event ABC and ds...\n\n";

$user = User::where('email', 'lkings013@gmail.com')->first();

if (!$user) {
    echo "❌ User not found!\n";
    exit(1);
}

echo "✅ User: {$user->full_name} (ID: {$user->id})\n\n";

// Events to register for
$eventIds = [15, 17]; // ds and Event ABC

foreach ($eventIds as $eventId) {
    $event = Event::find($eventId);

    if (!$event) {
        echo "❌ Event ID {$eventId} not found!\n";
        continue;
    }

    echo "Event: {$event->title} (ID: {$event->id})\n";
    echo "QR Code: {$event->qr_code_string}\n";

    // Check if already registered
    $existing = EventParticipant::where('user_id', $user->id)
        ->where('event_id', $event->id)
        ->first();

    if ($existing) {
        echo "⚠️  Already registered (Participant ID: {$existing->id})\n";
        echo "   Status: {$existing->status}\n";

        // Update to registered if needed
        if ($existing->status !== 'registered') {
            $existing->update(['status' => 'registered', 'attended_at' => null]);
            echo "   ✅ Updated to 'registered' status\n";
        }

        echo "\n";
        continue;
    }

    // Create participant
    $participant = EventParticipant::create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'status' => 'registered',
        'qr_code_string' => 'user_' . $user->id . '_event_' . $event->id . '_' . time() . '_' . uniqid(),
        'attended_at' => null,
    ]);

    echo "✅ Registered successfully! (Participant ID: {$participant->id})\n";
    echo "   Status: {$participant->status}\n\n";

    usleep(100000); // Small delay for unique IDs
}

echo "═══════════════════════════════════════════════════════\n";
echo "✅ Registration complete!\n\n";

echo "You can now test attendance with these QR codes:\n";
echo "─────────────────────────────────────────────────────────\n";

$event15 = Event::find(15);
$event17 = Event::find(17);

echo "Event 15 (ds):\n";
echo "   QR Code: {$event15->qr_code_string}\n\n";

echo "Event 17 (Event ABC):\n";
echo "   QR Code: {$event17->qr_code_string}\n\n";
