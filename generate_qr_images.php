<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Event;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;

echo "Generating QR code images for all events...\n\n";

$events = Event::whereNotNull('qr_code_string')->get();

if ($events->isEmpty()) {
    echo "❌ No events found with qr_code_string!\n";
    exit(1);
}

echo "Found {$events->count()} events to process:\n";
echo "═══════════════════════════════════════════════════════\n\n";

foreach ($events as $event) {
    echo "Processing Event ID {$event->id}: {$event->title}\n";
    echo "   QR String: {$event->qr_code_string}\n";

    // Generate QR code image path
    $qrCodePath = 'qr_codes/' . $event->qr_code_string . '.png';
    $fullPath = storage_path('app/public/' . $qrCodePath);

    // Create directory if it doesn't exist
    $directory = dirname($fullPath);
    if (!file_exists($directory)) {
        mkdir($directory, 0755, true);
        echo "   ✅ Created directory: {$directory}\n";
    }

    // Generate QR code image
    try {
        QrCode::format('png')
            ->size(300)
            ->margin(2)
            ->generate($event->qr_code_string, $fullPath);

        echo "   ✅ QR code image generated: {$qrCodePath}\n";

        // Update event with correct qr_code path
        $event->update(['qr_code' => $qrCodePath]);
        echo "   ✅ Event updated with image path\n\n";

    } catch (\Exception $e) {
        echo "   ❌ Error: {$e->getMessage()}\n\n";
    }
}

echo "═══════════════════════════════════════════════════════\n";
echo "✅ QR code images generated successfully!\n";
echo "\nImages saved to: storage/app/public/qr_codes/\n";
echo "Accessible via: http://localhost:8001/storage/qr_codes/\n";
