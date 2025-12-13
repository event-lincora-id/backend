<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Event;

echo "Generating QR codes for events without them...\n\n";

// Find all events without QR codes
$eventsWithoutQR = Event::whereNull('qr_code_string')->get();

if ($eventsWithoutQR->isEmpty()) {
    echo "✅ All events already have QR codes!\n";
    exit(0);
}

echo "Found " . $eventsWithoutQR->count() . " events without QR codes:\n";
echo "═══════════════════════════════════════════════════════\n\n";

foreach ($eventsWithoutQR as $event) {
    $qrCodeString = 'event_' . time() . '_' . uniqid();

    $event->update([
        'qr_code_string' => $qrCodeString
    ]);

    echo "✅ Event ID {$event->id}: {$event->title}\n";
    echo "   QR Code: {$qrCodeString}\n\n";

    // Small delay to ensure unique timestamps
    usleep(100000); // 0.1 second delay
}

echo "═══════════════════════════════════════════════════════\n";
echo "✅ All QR codes generated successfully!\n\n";

// Show summary
echo "Updated Events:\n";
echo "─────────────────────────────────────────────────────────\n";
$allEvents = Event::select('id', 'title', 'qr_code_string')->get();

foreach ($allEvents as $event) {
    echo "ID {$event->id}: {$event->title}\n";
    echo "   QR: {$event->qr_code_string}\n\n";
}
