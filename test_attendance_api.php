<?php
/**
 * Attendance API Test Script
 * Run with: php test_attendance_api.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\User;
use Illuminate\Support\Facades\Log;

echo "\n";
echo "═══════════════════════════════════════════════════════\n";
echo "         ATTENDANCE API TEST SCRIPT                     \n";
echo "═══════════════════════════════════════════════════════\n";
echo "\n";

// Get test data
$user = User::find(1);
$event = Event::find(4);
$participant = EventParticipant::where('user_id', 1)->where('event_id', 4)->first();

if (!$user || !$event || !$participant) {
    echo "❌ ERROR: Test data not found!\n";
    echo "   User ID 1: " . ($user ? "✓" : "✗") . "\n";
    echo "   Event ID 4: " . ($event ? "✓" : "✗") . "\n";
    echo "   Participant: " . ($participant ? "✓" : "✗") . "\n";
    exit(1);
}

echo "📋 TEST SETUP\n";
echo "─────────────────────────────────────────────────────────\n";
echo "User: {$user->full_name} (ID: {$user->id})\n";
echo "Event: {$event->title} (ID: {$event->id})\n";
echo "Event QR: {$event->qr_code_string}\n";
echo "Participant Status: {$participant->status}\n";
echo "\n";

// Ensure participant is in correct state
if ($participant->status !== 'registered') {
    echo "⚙️  Setting participant status to 'registered'...\n";
    $participant->update(['status' => 'registered', 'attended_at' => null]);
    $participant->refresh();
    echo "✅ Status updated to: {$participant->status}\n\n";
}

// Test 1: Mark attendance with Event QR
echo "═══════════════════════════════════════════════════════\n";
echo "TEST 1: Mark Attendance with Event QR Code\n";
echo "═══════════════════════════════════════════════════════\n";

try {
    $controller = new \App\Http\Controllers\Api\EventParticipantController();
    $request = new \Illuminate\Http\Request();
    $request->merge(['qr_code' => $event->qr_code_string]);
    $request->setUserResolver(function () use ($user) {
        return $user;
    });

    echo "📤 Request: POST /api/participants/attendance\n";
    echo "   QR Code: {$event->qr_code_string}\n";
    echo "   User ID: {$user->id}\n";
    echo "\n";

    $response = $controller->markAttendance($request);
    $data = json_decode($response->getContent(), true);

    echo "📥 Response:\n";
    echo "   Status Code: " . $response->getStatusCode() . "\n";
    echo "   Success: " . ($data['success'] ? '✅ true' : '❌ false') . "\n";
    echo "   Message: " . ($data['message'] ?? 'N/A') . "\n";

    if (isset($data['data'])) {
        echo "   Event: " . ($data['data']['event'] ?? 'N/A') . "\n";
        echo "   Attended At: " . ($data['data']['attended_at'] ?? 'N/A') . "\n";
    }

    // Verify in database
    $participant->refresh();
    echo "\n📊 Database Verification:\n";
    echo "   Status: {$participant->status}\n";
    echo "   Attended At: " . ($participant->attended_at ?? 'NULL') . "\n";

    if ($response->getStatusCode() === 200 && $participant->status === 'attended') {
        echo "\n✅ TEST 1 PASSED\n";
    } else {
        echo "\n❌ TEST 1 FAILED\n";
    }

} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\n❌ TEST 1 FAILED\n";
}

echo "\n";

// Test 2: Duplicate attendance prevention
echo "═══════════════════════════════════════════════════════\n";
echo "TEST 2: Duplicate Attendance Prevention\n";
echo "═══════════════════════════════════════════════════════\n";

try {
    $controller = new \App\Http\Controllers\Api\EventParticipantController();
    $request = new \Illuminate\Http\Request();
    $request->merge(['qr_code' => $event->qr_code_string]);
    $request->setUserResolver(function () use ($user) {
        return $user;
    });

    echo "📤 Request: POST /api/participants/attendance (2nd time)\n";
    echo "   QR Code: {$event->qr_code_string}\n";
    echo "\n";

    $response = $controller->markAttendance($request);
    $data = json_decode($response->getContent(), true);

    echo "📥 Response:\n";
    echo "   Status Code: " . $response->getStatusCode() . "\n";
    echo "   Success: " . ($data['success'] ? '✅ true' : '❌ false') . "\n";
    echo "   Message: " . ($data['message'] ?? 'N/A') . "\n";

    if ($response->getStatusCode() === 400 && !$data['success']) {
        echo "\n✅ TEST 2 PASSED (Correctly prevented duplicate)\n";
    } else {
        echo "\n❌ TEST 2 FAILED (Should have prevented duplicate)\n";
    }

} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\n❌ TEST 2 FAILED\n";
}

echo "\n";

// Test 3: Invalid Event QR Code
echo "═══════════════════════════════════════════════════════\n";
echo "TEST 3: Invalid Event QR Code\n";
echo "═══════════════════════════════════════════════════════\n";

try {
    $controller = new \App\Http\Controllers\Api\EventParticipantController();
    $request = new \Illuminate\Http\Request();
    $request->merge(['qr_code' => 'event_invalid_12345']);
    $request->setUserResolver(function () use ($user) {
        return $user;
    });

    echo "📤 Request: POST /api/participants/attendance\n";
    echo "   QR Code: event_invalid_12345 (fake)\n";
    echo "\n";

    $response = $controller->markAttendance($request);
    $data = json_decode($response->getContent(), true);

    echo "📥 Response:\n";
    echo "   Status Code: " . $response->getStatusCode() . "\n";
    echo "   Success: " . ($data['success'] ? '✅ true' : '❌ false') . "\n";
    echo "   Message: " . ($data['message'] ?? 'N/A') . "\n";

    if ($response->getStatusCode() === 404 && !$data['success']) {
        echo "\n✅ TEST 3 PASSED (Correctly rejected invalid QR)\n";
    } else {
        echo "\n❌ TEST 3 FAILED (Should have rejected invalid QR)\n";
    }

} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\n❌ TEST 3 FAILED\n";
}

echo "\n";

// Summary
echo "═══════════════════════════════════════════════════════\n";
echo "                    TEST SUMMARY                        \n";
echo "═══════════════════════════════════════════════════════\n";
echo "\n";
echo "All tests completed. Review results above.\n";
echo "\n";
echo "Next Steps:\n";
echo "1. If all tests passed ✅ - Frontend is ready to use\n";
echo "2. If any test failed ❌ - Fix Backend API first\n";
echo "\n";
