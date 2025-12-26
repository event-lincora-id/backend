<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Carbon\Carbon;

echo "=== Testing Attendance Reminder Command ===\n\n";

$now = Carbon::now('Asia/Jakarta');
echo "Current time: {$now}\n\n";

// Find ongoing events
$ongoingEvents = DB::table('events')
    ->where('start_date', '<=', $now)
    ->where('end_date', '>', $now)
    ->where('status', 'published')
    ->where('is_active', true)
    ->get(['id', 'title', 'start_date', 'end_date']);

echo "Ongoing events: " . count($ongoingEvents) . "\n";
foreach ($ongoingEvents as $event) {
    echo "  - Event #{$event->id}: {$event->title}\n";
    echo "    Start: {$event->start_date}\n";
    echo "    End: {$event->end_date}\n";

    // Get participants who haven't attended
    $participants = DB::table('event_participants')
        ->where('event_id', $event->id)
        ->where('status', 'registered')
        ->get(['id', 'user_id', 'status', 'attendance_reminder_sent', 'attendance_reminder_final_sent']);

    echo "    Participants not attended: " . count($participants) . "\n";
    foreach ($participants as $p) {
        echo "      - Participant #{$p->id} (user #{$p->user_id}): reminder_sent={$p->attendance_reminder_sent}, final_sent={$p->attendance_reminder_final_sent}\n";
    }
    echo "\n";
}

if (count($ongoingEvents) === 0) {
    echo "\n⚠️ No ongoing events found. The reminder command only runs for events that are currently happening.\n";
    echo "To test, you need an event with start_date <= now and end_date > now\n";
}
