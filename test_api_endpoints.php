<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\User;
use App\Models\OrganizerBalance;
use Illuminate\Support\Facades\Artisan;

echo "=== API ENDPOINT TESTING ===\n\n";

// Find test users
$organizer = User::where('role', 'admin')
    ->whereHas('organizerBalance')
    ->with('organizerBalance')
    ->first();

$superAdmin = User::where('role', 'super_admin')->first();

if (!$organizer) {
    die("❌ No organizer with balance found\n");
}

if (!$superAdmin) {
    die("❌ No super admin found\n");
}

echo "Test Users Found:\n";
echo "  Organizer: {$organizer->name} (ID: {$organizer->id})\n";
echo "  Super Admin: {$superAdmin->name} (ID: {$superAdmin->id})\n";
echo "  Organizer Balance: Rp " . number_format($organizer->organizerBalance->available_balance, 0, ',', '.') . "\n\n";

// Generate auth tokens
echo "Generating auth tokens...\n";
$organizerToken = $organizer->createToken('test-token')->plainTextToken;
$adminToken = $superAdmin->createToken('test-token')->plainTextToken;
echo "✅ Tokens generated\n\n";

// Base URL
$baseUrl = 'http://localhost:8001/api';

echo "=== TEST RESULTS ===\n\n";

// Test 1: Get Balance Dashboard (Organizer)
echo "1. GET /api/balance/dashboard (Organizer)\n";
echo "   Testing balance dashboard for organizer...\n";
$ch = curl_init("{$baseUrl}/balance/dashboard");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$organizerToken}",
    "Accept: application/json"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    echo "   ✅ Status: {$httpCode}\n";
    echo "   Balance: Rp " . number_format($data['data']['balance']['available_balance'], 0, ',', '.') . "\n";
    echo "   Total Earned: Rp " . number_format($data['data']['balance']['total_earned'], 0, ',', '.') . "\n";
    echo "   Platform Fee: Rp " . number_format($data['data']['balance']['platform_fee_total'], 0, ',', '.') . "\n";
} else {
    echo "   ❌ Status: {$httpCode}\n";
    echo "   Response: {$response}\n";
}
echo "\n";

// Test 2: Request Withdrawal (Organizer)
echo "2. POST /api/withdrawals/request (Organizer)\n";
echo "   Requesting withdrawal of Rp 100,000...\n";
$ch = curl_init("{$baseUrl}/withdrawals/request");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$organizerToken}",
    "Accept: application/json",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'amount' => 100000,
    'bank_name' => 'BCA',
    'bank_account_number' => '1234567890',
    'bank_account_holder' => $organizer->name
]));
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$withdrawalId = null;
if ($httpCode === 201) {
    $data = json_decode($response, true);
    $withdrawalId = $data['data']['withdrawal_request']['id'];
    echo "   ✅ Status: {$httpCode}\n";
    echo "   Withdrawal ID: {$withdrawalId}\n";
    echo "   Status: {$data['data']['withdrawal_request']['status']}\n";
    echo "   New Available Balance: Rp " . number_format($data['data']['balance']['available_balance'], 0, ',', '.') . "\n";
} else {
    echo "   ❌ Status: {$httpCode}\n";
    echo "   Response: {$response}\n";
}
echo "\n";

// Test 3: Get Withdrawal History (Organizer)
echo "3. GET /api/withdrawals/history (Organizer)\n";
echo "   Getting withdrawal history...\n";
$ch = curl_init("{$baseUrl}/withdrawals/history");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$organizerToken}",
    "Accept: application/json"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    echo "   ✅ Status: {$httpCode}\n";
    echo "   Total Requests: {$data['data']['summary']['total_requests']}\n";
    echo "   Pending: {$data['data']['summary']['pending']}\n";
} else {
    echo "   ❌ Status: {$httpCode}\n";
    echo "   Response: {$response}\n";
}
echo "\n";

// Test 4: Get All Withdrawal Requests (Super Admin)
echo "4. GET /api/admin/withdrawals (Super Admin)\n";
echo "   Getting all withdrawal requests...\n";
$ch = curl_init("{$baseUrl}/admin/withdrawals");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$adminToken}",
    "Accept: application/json"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    echo "   ✅ Status: {$httpCode}\n";
    echo "   Total Pending: {$data['data']['summary']['pending']}\n";
    echo "   Total Approved: {$data['data']['summary']['approved']}\n";
    echo "   Total Rejected: {$data['data']['summary']['rejected']}\n";
} else {
    echo "   ❌ Status: {$httpCode}\n";
    echo "   Response: {$response}\n";
}
echo "\n";

if ($withdrawalId) {
    // Test 5: Approve Withdrawal (Super Admin)
    echo "5. POST /api/admin/withdrawals/{$withdrawalId}/approve (Super Admin)\n";
    echo "   Approving withdrawal request...\n";
    $ch = curl_init("{$baseUrl}/admin/withdrawals/{$withdrawalId}/approve");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$adminToken}",
        "Accept: application/json",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'admin_notes' => 'Approved - funds will be transferred within 1-3 business days'
    ]));
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $data = json_decode($response, true);
        echo "   ✅ Status: {$httpCode}\n";
        echo "   New Status: {$data['data']['withdrawal_request']['status']}\n";
        echo "   Approved By: {$data['data']['withdrawal_request']['admin']['name']}\n";
    } else {
        echo "   ❌ Status: {$httpCode}\n";
        echo "   Response: {$response}\n";
    }
    echo "\n";

    // Test 6: Check updated balance
    echo "6. GET /api/balance/dashboard (After Approval)\n";
    echo "   Checking updated balance...\n";
    $ch = curl_init("{$baseUrl}/balance/dashboard");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$organizerToken}",
        "Accept: application/json"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $data = json_decode($response, true);
        echo "   ✅ Status: {$httpCode}\n";
        echo "   Available Balance: Rp " . number_format($data['data']['balance']['available_balance'], 0, ',', '.') . "\n";
        echo "   Withdrawn: Rp " . number_format($data['data']['balance']['withdrawn'], 0, ',', '.') . "\n";
        echo "   Pending: Rp " . number_format($data['data']['balance']['pending_withdrawal'], 0, ',', '.') . "\n";
    } else {
        echo "   ❌ Status: {$httpCode}\n";
    }
    echo "\n";
}

// Cleanup tokens
echo "Cleaning up test tokens...\n";
$organizer->tokens()->delete();
$superAdmin->tokens()->delete();
echo "✅ Cleanup complete\n\n";

echo "=== ALL TESTS COMPLETE ===\n";
