<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\EventParticipant;

$user = User::where('email', 'lkings013@gmail.com')->first();

if (!$user) {
    echo "❌ User not found!\n";
    exit(1);
}

echo "User: {$user->full_name} (ID: {$user->id})\n";
echo "Email: {$user->email}\n\n";

$participants = EventParticipant::where('user_id', $user->id)
    ->with('event')
    ->get();

if ($participants->isEmpty()) {
    echo "❌ You are not registered for any events yet!\n";
    exit(0);
}

echo "Events you are registered for:\n";
echo "═══════════════════════════════════════════════════════\n\n";

foreach ($participants as $p) {
    echo "Event ID {$p->event_id}: {$p->event->title}\n";
    echo "   Participant ID: {$p->id}\n";
    echo "   Status: {$p->status}\n";
    echo "   Event QR Code: {$p->event->qr_code_string}\n";
    echo "   Attended At: " . ($p->attended_at ?? 'Not yet') . "\n\n";
}

echo "Total: " . $participants->count() . " events\n";
