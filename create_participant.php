<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Event;
use App\Models\EventParticipant;

echo "Creating participant record for lkings013@gmail.com...\n\n";

// Find the user
$user = User::where('email', 'lkings013@gmail.com')->first();

if (!$user) {
    echo "❌ User not found!\n";
    exit(1);
}

echo "✅ User found:\n";
echo "   ID: {$user->id}\n";
echo "   Name: {$user->full_name}\n";
echo "   Email: {$user->email}\n\n";

// Find the event
$event = Event::find(4);

if (!$event) {
    echo "❌ Event ID 4 not found!\n";
    exit(1);
}

echo "✅ Event found:\n";
echo "   ID: {$event->id}\n";
echo "   Title: {$event->title}\n";
echo "   QR Code: {$event->qr_code_string}\n\n";

// Check if participant already exists
$existing = EventParticipant::where('user_id', $user->id)
    ->where('event_id', $event->id)
    ->first();

if ($existing) {
    echo "⚠️  Participant record already exists:\n";
    echo "   ID: {$existing->id}\n";
    echo "   Status: {$existing->status}\n";
    echo "   Attended At: " . ($existing->attended_at ?? 'NULL') . "\n\n";

    // Update to registered status
    echo "Updating status to 'registered'...\n";
    $existing->update([
        'status' => 'registered',
        'attended_at' => null
    ]);

    echo "✅ Updated successfully!\n";
    exit(0);
}

// Create new participant
$participant = EventParticipant::create([
    'user_id' => $user->id,
    'event_id' => $event->id,
    'status' => 'registered',
    'qr_code_string' => 'user_' . $user->id . '_event_' . $event->id . '_' . time() . '_' . uniqid(),
    'attended_at' => null,
]);

echo "✅ Participant created successfully!\n";
echo "   ID: {$participant->id}\n";
echo "   User ID: {$participant->user_id}\n";
echo "   Event ID: {$participant->event_id}\n";
echo "   Status: {$participant->status}\n";
echo "   QR Code: {$participant->qr_code_string}\n\n";

echo "✅ Ready to test! Use Event QR code: {$event->qr_code_string}\n";
