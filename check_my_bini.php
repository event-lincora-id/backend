<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\EventParticipant;
use App\Models\Notification;

$user = User::where('full_name', 'My Bini')->first();

if (!$user) {
    echo "User 'My Bini' not found\n";
    exit(1);
}

echo "User 'My Bini' ID: {$user->id}\n";
echo "Email: {$user->email}\n";
echo "Role: {$user->role}\n\n";

echo "=== Participations ===\n";
$participations = EventParticipant::where('user_id', $user->id)->with('event')->get();
foreach ($participations as $p) {
    echo "- Event: {$p->event->title} (ID: {$p->event_id})\n";
    echo "  Status: {$p->status}, Paid: " . ($p->is_paid ? 'YES' : 'NO') . "\n";
    echo "  Registered at: {$p->created_at}\n\n";
}

echo "=== Notifications ===\n";
$notifs = Notification::where('user_id', $user->id)->orderBy('created_at', 'desc')->get();
echo "Total: {$notifs->count()}\n";

if ($notifs->count() > 0) {
    foreach ($notifs as $n) {
        echo "\nID: {$n->id}\n";
        echo "Type: {$n->type}\n";
        echo "Title: {$n->title}\n";
        echo "Message: {$n->message}\n";
        echo "Created: {$n->created_at}\n";
    }
} else {
    echo "\nNo notifications found for My Bini.\n";
    echo "\nThis is EXPECTED behavior - participants only receive EMAIL confirmations,\n";
    echo "not in-app notifications. In-app notifications are for event organizers.\n";
}
