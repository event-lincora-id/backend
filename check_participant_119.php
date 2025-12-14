<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CHECKING PARTICIPANT #119 (Your Recent Test) ===" . PHP_EOL . PHP_EOL;

$participant = App\Models\EventParticipant::with('event', 'user')->find(119);

if (!$participant) {
    echo "Participant #119 not found!" . PHP_EOL;
    exit;
}

echo "Participant ID: {$participant->id}" . PHP_EOL;
echo "User: {$participant->user->name} ({$participant->user->email})" . PHP_EOL;
echo "Event: {$participant->event->title} (ID: {$participant->event_id})" . PHP_EOL;
echo "Event Price: Rp {$participant->event->price}" . PHP_EOL;
echo PHP_EOL;

echo "=== CURRENT STATUS ===" . PHP_EOL;
echo "Status: {$participant->status}" . PHP_EOL;
echo "Payment Status: {$participant->payment_status}" . PHP_EOL;
echo "Is Paid: " . ($participant->is_paid ? 'Yes' : 'No') . PHP_EOL;
echo "Payment Reference: {$participant->payment_reference}" . PHP_EOL;
echo "Payment URL: " . ($participant->payment_url ?? 'null') . PHP_EOL;
echo "Amount Paid: " . ($participant->amount_paid ?? 'null') . PHP_EOL;
echo "Paid At: " . ($participant->paid_at ?? 'null') . PHP_EOL;
echo "Created At: {$participant->created_at}" . PHP_EOL;
echo PHP_EOL;

echo "=== SIMULATING: User Returns from Xendit After Paying ===" . PHP_EOL;
echo "The frontend would call: GET /api/payments/participants/{$participant->id}/status" . PHP_EOL;
echo "This endpoint would:" . PHP_EOL;
echo "1. Check payment status from Xendit API using payment_reference: {$participant->payment_reference}" . PHP_EOL;
echo "2. Get response from Xendit (e.g., status='PAID', amount=100000)" . PHP_EOL;
echo "3. Update participant fields..." . PHP_EOL;
echo PHP_EOL;

echo "=== CURRENT BUG IN getPaymentStatus() (lines 239-246) ===" . PHP_EOL;
echo "Current code ONLY updates:" . PHP_EOL;
echo "  - payment_status = 'paid'" . PHP_EOL;
echo "  - is_paid = true" . PHP_EOL;
echo "  - amount_paid = (from Xendit)" . PHP_EOL;
echo "  - paid_at = now()" . PHP_EOL;
echo PHP_EOL;
echo "Current code DOES NOT update:" . PHP_EOL;
echo "  ❌ status field (stays 'pending_payment' instead of changing to 'registered')" . PHP_EOL;
echo "  ❌ registered_count (stays {$participant->event->registered_count} instead of incrementing)" . PHP_EOL;
echo PHP_EOL;

echo "=== EXPECTED BEHAVIOR AFTER FIX ===" . PHP_EOL;
echo "After fix, getPaymentStatus() should update:" . PHP_EOL;
echo "  ✅ payment_status = 'paid'" . PHP_EOL;
echo "  ✅ is_paid = true" . PHP_EOL;
echo "  ✅ amount_paid = (from Xendit)" . PHP_EOL;
echo "  ✅ paid_at = now()" . PHP_EOL;
echo "  ✅ status = 'registered' (ADDED IN FIX)" . PHP_EOL;
echo "  ✅ Event registered_count incremented to " . ($participant->event->registered_count + 1) . " (ADDED IN FIX)" . PHP_EOL;
echo PHP_EOL;

echo "=== TESTING: Can we detect if user actually paid via Xendit? ===" . PHP_EOL;
echo "To properly test, we would need to:" . PHP_EOL;
echo "1. Call Xendit API: GET /v2/invoices/{$participant->payment_reference}" . PHP_EOL;
echo "2. Check if status is 'PAID' or 'SETTLED'" . PHP_EOL;
echo "3. If paid, update both payment_status AND status field" . PHP_EOL;
echo "4. Increment registered_count" . PHP_EOL;
echo PHP_EOL;

echo "Would you like me to:" . PHP_EOL;
echo "A) Implement the fix to getPaymentStatus() method" . PHP_EOL;
echo "B) First test calling Xendit API to check if participant #119 actually paid" . PHP_EOL;
echo "C) Check the PaymentService to see how it queries Xendit" . PHP_EOL;
