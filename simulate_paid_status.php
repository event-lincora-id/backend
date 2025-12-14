<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\EventParticipant;

echo "=== SIMULATING: What Happens When User PAYS ===" . PHP_EOL . PHP_EOL;

// Get participant 119
$participant = EventParticipant::with('event')->find(119);

if (!$participant) {
    echo "Participant #119 not found!" . PHP_EOL;
    exit;
}

echo "SCENARIO: User just paid on Xendit. Xendit API now returns status='PAID'" . PHP_EOL;
echo PHP_EOL;

echo "=== CURRENT STATE (Before getPaymentStatus() is called) ===" . PHP_EOL;
echo "Participant Status: {$participant->status}" . PHP_EOL;
echo "Payment Status: {$participant->payment_status}" . PHP_EOL;
echo "Is Paid: " . ($participant->is_paid ? 'Yes' : 'No') . PHP_EOL;
echo "Event registered_count: {$participant->event->registered_count}" . PHP_EOL;
echo PHP_EOL;

echo "=== SIMULATING getPaymentStatus() WITH PAID STATUS ===" . PHP_EOL;
echo "Imagine Xendit API returned:" . PHP_EOL;
$simulatedStatus = [
    'success' => true,
    'status' => 'PAID',  // User paid!
    'amount' => 100000,
    'paid_amount' => 100000,  // Full amount paid
];
echo "  Status: PAID" . PHP_EOL;
echo "  Paid Amount: Rp 100,000" . PHP_EOL;
echo PHP_EOL;

echo "=== OLD CODE (Before Fix) ===" . PHP_EOL;
echo "Would update:" . PHP_EOL;
echo "  ✅ payment_status = 'paid'" . PHP_EOL;
echo "  ✅ is_paid = true" . PHP_EOL;
echo "  ✅ amount_paid = 100000" . PHP_EOL;
echo "  ✅ paid_at = now()" . PHP_EOL;
echo PHP_EOL;
echo "Would NOT update:" . PHP_EOL;
echo "  ❌ status (stays 'pending_payment')" . PHP_EOL;
echo "  ❌ registered_count (stays {$participant->event->registered_count})" . PHP_EOL;
echo PHP_EOL;
echo "RESULT: User paid but can't attend! Bug!" . PHP_EOL;
echo PHP_EOL;

echo "=== NEW CODE (After Fix) ===" . PHP_EOL;
echo "Will update:" . PHP_EOL;
echo "  ✅ payment_status = 'paid'" . PHP_EOL;
echo "  ✅ is_paid = true" . PHP_EOL;
echo "  ✅ amount_paid = 100000" . PHP_EOL;
echo "  ✅ paid_at = now()" . PHP_EOL;
echo "  ✅ status = 'registered' (FIXED!)" . PHP_EOL;
echo "  ✅ registered_count = " . ($participant->event->registered_count + 1) . " (FIXED!)" . PHP_EOL;
echo PHP_EOL;
echo "RESULT: User can attend and count is accurate!" . PHP_EOL;
echo PHP_EOL;

echo "=== TO TEST THIS FIX ===" . PHP_EOL;
echo "1. Go to event page: http://localhost:8002/events/5" . PHP_EOL;
echo "2. Click 'Pay Rp 100,000' button" . PHP_EOL;
echo "3. Get redirected to Xendit staging" . PHP_EOL;
echo "4. Use test card: 4000000000001091 (any CVV, any future date)" . PHP_EOL;
echo "5. Complete payment" . PHP_EOL;
echo "6. Return to site" . PHP_EOL;
echo "7. Check participant #119 status - should be 'registered'!" . PHP_EOL;
echo "8. Check event registered_count - should be " . ($participant->event->registered_count + 1) . "!" . PHP_EOL;
echo PHP_EOL;

echo "NOTE: You can also trigger this by calling the API endpoint:" . PHP_EOL;
echo "  GET /api/payments/participants/119/status" . PHP_EOL;
echo "  Authorization: Bearer {your_token}" . PHP_EOL;
