<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Event;

echo "=== Checking All Full Events ===\n\n";

// Get all events that are full but not notified
$fullEvents = Event::whereNotNull('quota')
    ->whereRaw('registered_count >= quota')
    ->get();

echo "Total Full Events: " . $fullEvents->count() . "\n\n";

if ($fullEvents->count() == 0) {
    echo "No full events found.\n";
    exit(0);
}

foreach ($fullEvents as $event) {
    echo "─────────────────────────────────────\n";
    echo "Event ID: {$event->id}\n";
    echo "Title: {$event->title}\n";
    echo "Organizer: {$event->organizer->full_name} (ID: {$event->user_id})\n";
    echo "Registered: {$event->registered_count} / {$event->quota}\n";
    echo "Is Paid: " . ($event->is_paid ? "YES (Rp " . number_format($event->price, 0, ',', '.') . ")" : "FREE") . "\n";
    echo "Quota Full Notified: " . ($event->quota_full_notified ? "YES ✅" : "NO ❌") . "\n";

    if ($event->is_paid) {
        $totalRevenue = App\Models\EventParticipant::where('event_id', $event->id)
            ->where('is_paid', true)
            ->sum('amount_paid');
        echo "Total Revenue: Rp " . number_format($totalRevenue, 0, ',', '.') . "\n";
    }
    echo "\n";
}

echo "─────────────────────────────────────\n";
echo "\nEvents that NEED notification:\n";
$needNotification = $fullEvents->filter(fn($e) => !$e->quota_full_notified);
echo "Count: " . $needNotification->count() . "\n";

if ($needNotification->count() > 0) {
    echo "IDs: " . $needNotification->pluck('id')->implode(', ') . "\n";
}
