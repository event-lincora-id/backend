<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\EventParticipant;

echo "Checking participants for Event ID 15...\n\n";

$participants = EventParticipant::where('event_id', 15)->with('user')->get();

echo "Total participants: {$participants->count()}\n";
echo "═══════════════════════════════════════════════════════\n\n";

if ($participants->isEmpty()) {
    echo "❌ No participants found for Event ID 15!\n";
    exit(1);
}

foreach ($participants as $p) {
    echo "Participant ID: {$p->id}\n";
    echo "   User ID: {$p->user_id}\n";
    echo "   User Name: {$p->user->full_name}\n";
    echo "   Email: {$p->user->email}\n";
    echo "   Status: {$p->status}\n";
    echo "   Attended At: " . ($p->attended_at ?? 'NULL') . "\n";
    echo "   Registered At: {$p->created_at}\n\n";
}

echo "═══════════════════════════════════════════════════════\n";
echo "Stats:\n";
echo "   Registered: " . $participants->where('status', 'registered')->count() . "\n";
echo "   Attended: " . $participants->where('status', 'attended')->count() . "\n";
echo "   Pending Payment: " . $participants->where('status', 'pending_payment')->count() . "\n";
