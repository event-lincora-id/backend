<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CHECKING RECENT PAYMENT HISTORY ===" . PHP_EOL . PHP_EOL;

// Find events mentioned in the screenshot
$events = [
    'UI/UX Design Bootcamp',
    'Digital Marketing Masterclass',
    'Startup Pitch Competition'
];

foreach ($events as $eventName) {
    $event = App\Models\Event::where('title', 'LIKE', "%{$eventName}%")->first();

    if ($event) {
        echo "=== {$event->title} (ID: {$event->id}) ===" . PHP_EOL;
        echo "Event Price: Rp " . number_format($event->price) . PHP_EOL;

        // Get recent participants
        $participants = App\Models\EventParticipant::where('event_id', $event->id)
            ->whereNotNull('payment_reference')
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        echo "Recent participants with payments:" . PHP_EOL;
        foreach ($participants as $p) {
            echo "  - User: {$p->user->name} ({$p->user->email})" . PHP_EOL;
            echo "    Amount Paid: Rp " . number_format($p->amount_paid ?? 0) . PHP_EOL;
            echo "    Payment Status: {$p->payment_status}" . PHP_EOL;
            echo "    Status: {$p->status}" . PHP_EOL;
            echo "    Is Paid: " . ($p->is_paid ? 'Yes' : 'No') . PHP_EOL;
            echo "    Created: {$p->created_at}" . PHP_EOL;

            if ($p->is_paid && ($p->amount_paid == 0 || $p->amount_paid === null) && $event->price > 0) {
                echo "    🚨 BUG: Paid but amount_paid is " . ($p->amount_paid ?? 'NULL') . "!" . PHP_EOL;
            }
            echo PHP_EOL;
        }
        echo PHP_EOL;
    }
}

// Also check for any participants with is_paid=true but amount_paid=0
echo "=== PARTICIPANTS WITH is_paid=true BUT amount_paid=0 ===" . PHP_EOL;
$buggedParticipants = App\Models\EventParticipant::where('is_paid', true)
    ->where(function($q) {
        $q->where('amount_paid', 0)
          ->orWhereNull('amount_paid');
    })
    ->whereNotNull('payment_reference')
    ->with(['event', 'user'])
    ->get();

foreach ($buggedParticipants as $p) {
    if ($p->event->price > 0) {  // Only show if event is NOT free
        echo "Event: {$p->event->title} (Price: Rp " . number_format($p->event->price) . ")" . PHP_EOL;
        echo "  User: {$p->user->name}" . PHP_EOL;
        echo "  Amount Paid: " . ($p->amount_paid ?? 'NULL') . PHP_EOL;
        echo "  Payment Status: {$p->payment_status}" . PHP_EOL;
        echo "  Status: {$p->status}" . PHP_EOL;
        echo "  Payment Reference: {$p->payment_reference}" . PHP_EOL;
        echo "  Created: {$p->created_at}" . PHP_EOL;
        echo PHP_EOL;
    }
}

if ($buggedParticipants->isEmpty()) {
    echo "No issues found - all paid participants have correct amounts!" . PHP_EOL;
}
