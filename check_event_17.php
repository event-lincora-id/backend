<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$event = App\Models\Event::find(17);

if ($event) {
    echo "Event ID: {$event->id}" . PHP_EOL;
    echo "Title: {$event->title}" . PHP_EOL;
    echo "qr_code: {$event->qr_code}" . PHP_EOL;
    echo "qr_code_string: {$event->qr_code_string}" . PHP_EOL;
} else {
    echo "Event 17 not found" . PHP_EOL;
}
