<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\EventParticipant;

$participant = EventParticipant::where('user_id', 1)
    ->where('event_id', 4)
    ->first();

if ($participant) {
    $participant->update([
        'status' => 'registered',
        'attended_at' => null
    ]);

    echo "✅ Participant reset successfully!\n";
    echo "   User ID: {$participant->user_id}\n";
    echo "   Event ID: {$participant->event_id}\n";
    echo "   Status: {$participant->status}\n";
    echo "   Attended At: " . ($participant->attended_at ?? 'NULL') . "\n";
} else {
    echo "❌ Participant not found!\n";
}
