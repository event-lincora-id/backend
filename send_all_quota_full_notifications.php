<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Event;
use App\Models\EventParticipant;
use Illuminate\Support\Facades\Mail;
use App\Mail\Organizer\QuotaFullMail;

echo "=== Sending Quota Full Notifications ===\n\n";

// Get all events that are full but not notified
$fullEvents = Event::whereNotNull('quota')
    ->whereRaw('registered_count >= quota')
    ->where('quota_full_notified', false)
    ->get();

echo "Events needing notification: " . $fullEvents->count() . "\n\n";

if ($fullEvents->count() == 0) {
    echo "No events need quota full notification.\n";
    exit(0);
}

$successCount = 0;
$failCount = 0;

foreach ($fullEvents as $event) {
    echo "Processing Event ID {$event->id}: {$event->title}\n";
    echo "  Organizer: {$event->organizer->full_name}\n";
    echo "  Registered: {$event->registered_count} / {$event->quota}\n";

    try {
        // Calculate total revenue for paid events
        $totalRevenue = null;
        if ($event->price > 0) {
            $totalRevenue = EventParticipant::where('event_id', $event->id)
                ->where('is_paid', true)
                ->sum('amount_paid');
            echo "  Revenue: Rp " . number_format($totalRevenue, 0, ',', '.') . "\n";
        }

        // Send email
        Mail::to($event->organizer->email)->send(
            new QuotaFullMail($event, $event->organizer, $totalRevenue)
        );

        // Mark as notified
        $event->update(['quota_full_notified' => true]);

        echo "  ✅ Notification sent to {$event->organizer->email}\n\n";
        $successCount++;

    } catch (\Exception $e) {
        echo "  ❌ Error: " . $e->getMessage() . "\n\n";
        $failCount++;
    }
}

echo "─────────────────────────────────────\n";
echo "Summary:\n";
echo "  Success: $successCount\n";
echo "  Failed: $failCount\n";
echo "  Total: " . $fullEvents->count() . "\n";
