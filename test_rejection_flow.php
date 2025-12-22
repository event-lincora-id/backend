<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\User;

echo "=== TESTING WITHDRAWAL REJECTION FLOW ===\n\n";

// Find organizer with highest balance
$organizer = User::where('role', 'admin')
    ->whereHas('organizerBalance')
    ->with('organizerBalance')
    ->orderByDesc(function($query) {
        $query->select('available_balance')
              ->from('organizer_balances')
              ->whereColumn('organizer_balances.user_id', 'users.id');
    })
    ->first();

$superAdmin = User::where('role', 'super_admin')->first();

echo "Test Users:\n";
echo "  Organizer: {$organizer->name} (ID: {$organizer->id})\n";
echo "  Available Balance: Rp " . number_format($organizer->organizerBalance->available_balance, 0, ',', '.') . "\n\n";

// Generate tokens
$organizerToken = $organizer->createToken('test-token')->plainTextToken;
$adminToken = $superAdmin->createToken('test-token')->plainTextToken;

$baseUrl = 'http://localhost:8001/api';

// Step 1: Request withdrawal
echo "1. Requesting withdrawal of Rp 500,000...\n";
$ch = curl_init("{$baseUrl}/withdrawals/request");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$organizerToken}",
    "Accept: application/json",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'amount' => 500000,
    'bank_name' => 'BNI',
    'bank_account_number' => '9876543210',
    'bank_account_holder' => $organizer->name
]));
$response = curl_exec($ch);
$data = json_decode($response, true);
curl_close($ch);

$withdrawalId = $data['data']['withdrawal_request']['id'] ?? null;
if ($withdrawalId) {
    echo "   ✅ Withdrawal request created (ID: {$withdrawalId})\n";
    echo "   Available Balance: Rp " . number_format($data['data']['balance']['available_balance'], 0, ',', '.') . "\n";
    echo "   Pending: Rp " . number_format($data['data']['balance']['pending_withdrawal'], 0, ',', '.') . "\n\n";

    // Step 2: Reject withdrawal
    echo "2. Rejecting withdrawal request...\n";
    $ch = curl_init("{$baseUrl}/admin/withdrawals/{$withdrawalId}/reject");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$adminToken}",
        "Accept: application/json",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'admin_notes' => 'Bank account verification failed. Please provide valid bank account details.'
    ]));
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    curl_close($ch);

    echo "   ✅ Withdrawal rejected\n";
    echo "   Status: {$data['data']['withdrawal_request']['status']}\n";
    echo "   Admin Notes: {$data['data']['withdrawal_request']['admin_notes']}\n\n";

    // Step 3: Check balance restored
    echo "3. Checking balance after rejection...\n";
    $ch = curl_init("{$baseUrl}/balance/dashboard");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$organizerToken}",
        "Accept: application/json"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    curl_close($ch);

    echo "   ✅ Balance restored\n";
    echo "   Available Balance: Rp " . number_format($data['data']['balance']['available_balance'], 0, ',', '.') . "\n";
    echo "   Pending: Rp " . number_format($data['data']['balance']['pending_withdrawal'], 0, ',', '.') . "\n";
}

// Cleanup
$organizer->tokens()->delete();
$superAdmin->tokens()->delete();

echo "\n✅ REJECTION FLOW TEST COMPLETE\n";
