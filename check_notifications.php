<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Notification;

echo "=== Checking Recent Notifications ===\n\n";

$notifications = Notification::latest()->take(5)->get();

foreach ($notifications as $notif) {
    echo "ID: {$notif->id}\n";
    echo "User ID: {$notif->user_id}\n";
    echo "Event ID: " . ($notif->event_id ?? 'NULL') . "\n";
    echo "Type: {$notif->type}\n";
    echo "Title: {$notif->title}\n";
    echo "Message: {$notif->message}\n";
    echo "Is Read: " . ($notif->is_read ? 'YES' : 'NO') . "\n";
    echo "Created: {$notif->created_at}\n";
    echo "---\n\n";
}
