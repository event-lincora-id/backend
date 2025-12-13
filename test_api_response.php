<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Event;
use App\Models\User;

echo "Testing Backend API Response for Event ID 15...\n\n";

// Get event from database
$event = Event::find(15);

if (!$event) {
    echo "❌ Event not found!\n";
    exit(1);
}

echo "Event from Database:\n";
echo "═══════════════════════════════════════════════════════\n";
echo "ID: {$event->id}\n";
echo "Title: {$event->title}\n";
echo "qr_code (image path): {$event->qr_code}\n";
echo "qr_code_string: {$event->qr_code_string}\n\n";

// Simulate API response
echo "Event toArray() output (what API returns):\n";
echo "═══════════════════════════════════════════════════════\n";
$eventArray = $event->toArray();

if (isset($eventArray['qr_code_string'])) {
    echo "✅ qr_code_string IS included in API response\n";
    echo "   Value: {$eventArray['qr_code_string']}\n\n";
} else {
    echo "❌ qr_code_string NOT included in API response\n";
    echo "   This is the problem!\n\n";
}

echo "Full event data that API returns:\n";
echo json_encode($eventArray, JSON_PRETTY_PRINT);
echo "\n\n";

// Check if hidden
$reflection = new \ReflectionClass($event);
$hiddenProperty = $reflection->getProperty('hidden');
$hiddenProperty->setAccessible(true);
$hiddenFields = $hiddenProperty->getValue($event);

echo "Hidden fields in Event model:\n";
echo "═══════════════════════════════════════════════════════\n";
if (empty($hiddenFields)) {
    echo "✅ No hidden fields\n";
} else {
    print_r($hiddenFields);
}
