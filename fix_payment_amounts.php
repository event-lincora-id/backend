<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== FIXING PAYMENT AMOUNTS ===" . PHP_EOL . PHP_EOL;

// Find all participants with is_paid=true but amount_paid=0
$buggedParticipants = App\Models\EventParticipant::where('is_paid', true)
    ->where(function($q) {
        $q->where('amount_paid', 0)
          ->orWhereNull('amount_paid');
    })
    ->with('event')
    ->get();

echo "Found {$buggedParticipants->count()} participants with payment amount issues" . PHP_EOL . PHP_EOL;

foreach ($buggedParticipants as $p) {
    if ($p->event->price > 0) {  // Only fix if event is NOT free
        echo "Fixing: Event '{$p->event->title}' (ID: {$p->id})" . PHP_EOL;
        echo "  Current amount_paid: " . ($p->amount_paid ?? 'NULL') . PHP_EOL;
        echo "  Event price: Rp " . number_format($p->event->price) . PHP_EOL;

        $p->update([
            'amount_paid' => $p->event->price
        ]);

        echo "  ✅ Updated to: Rp " . number_format($p->event->price) . PHP_EOL;
        echo PHP_EOL;
    }
}

echo "=== VERIFICATION ===" . PHP_EOL;
$stillBugged = App\Models\EventParticipant::where('is_paid', true)
    ->where(function($q) {
        $q->where('amount_paid', 0)
          ->orWhereNull('amount_paid');
    })
    ->whereHas('event', function($q) {
        $q->where('price', '>', 0);
    })
    ->count();

if ($stillBugged > 0) {
    echo "⚠️ Still have {$stillBugged} participants with issues!" . PHP_EOL;
} else {
    echo "✅ All payment amounts fixed!" . PHP_EOL;
}
