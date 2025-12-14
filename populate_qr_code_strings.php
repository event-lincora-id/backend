<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== POPULATING QR CODE STRINGS ===" . PHP_EOL . PHP_EOL;

// Find all events that have qr_code but no qr_code_string
$events = App\Models\Event::whereNotNull('qr_code')
    ->where(function($q) {
        $q->whereNull('qr_code_string')
          ->orWhere('qr_code_string', '');
    })
    ->get();

echo "Found {$events->count()} events without qr_code_string" . PHP_EOL . PHP_EOL;

foreach ($events as $event) {
    echo "Processing Event ID {$event->id}: {$event->title}" . PHP_EOL;
    echo "  Current qr_code: {$event->qr_code}" . PHP_EOL;

    // Extract the string from the PNG path or use the value directly
    if (str_contains($event->qr_code, '.png')) {
        // It's a PNG path like "qr_codes/event_1234567890_abc123.png"
        $qrString = basename($event->qr_code, '.png');
        echo "  Extracted from PNG path: {$qrString}" . PHP_EOL;
    } else {
        // It's already a string
        $qrString = $event->qr_code;
        echo "  Already a string: {$qrString}" . PHP_EOL;
    }

    $event->update(['qr_code_string' => $qrString]);
    echo "  ✅ Updated qr_code_string" . PHP_EOL;
    echo PHP_EOL;
}

echo "=== VERIFICATION ===" . PHP_EOL;
$missingQrString = App\Models\Event::whereNotNull('qr_code')
    ->where(function($q) {
        $q->whereNull('qr_code_string')
          ->orWhere('qr_code_string', '');
    })
    ->count();

if ($missingQrString > 0) {
    echo "⚠️ Still have {$missingQrString} events without qr_code_string!" . PHP_EOL;
} else {
    echo "✅ All events have qr_code_string populated!" . PHP_EOL;
}
