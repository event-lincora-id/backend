<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\EventParticipant;
use App\Services\PaymentService;

echo "=== TESTING PAYMENT VERIFICATION FIX ===" . PHP_EOL . PHP_EOL;

// Get participant 119
$participant = EventParticipant::with('event')->find(119);

if (!$participant) {
    echo "Participant #119 not found!" . PHP_EOL;
    exit;
}

echo "=== BEFORE STATUS CHECK ===" . PHP_EOL;
echo "Participant ID: {$participant->id}" . PHP_EOL;
echo "Event: {$participant->event->title} (ID: {$participant->event_id})" . PHP_EOL;
echo "Status: {$participant->status}" . PHP_EOL;
echo "Payment Status: {$participant->payment_status}" . PHP_EOL;
echo "Is Paid: " . ($participant->is_paid ? 'Yes' : 'No') . PHP_EOL;
echo "Payment Reference: {$participant->payment_reference}" . PHP_EOL;
echo "Event registered_count: {$participant->event->registered_count}" . PHP_EOL;
echo PHP_EOL;

echo "=== CALLING XENDIT API TO CHECK PAYMENT STATUS ===" . PHP_EOL;
$paymentService = new PaymentService();
$status = $paymentService->getPaymentStatus($participant->payment_reference);

echo "API Response:" . PHP_EOL;
echo "  Success: " . ($status['success'] ? 'Yes' : 'No') . PHP_EOL;
if ($status['success']) {
    echo "  Status from Xendit: {$status['status']}" . PHP_EOL;
    echo "  Amount: " . ($status['amount'] ?? 'null') . PHP_EOL;
    echo "  Paid Amount: " . ($status['paid_amount'] ?? 'null') . PHP_EOL;
} else {
    echo "  Error: " . ($status['error'] ?? 'unknown') . PHP_EOL;
}
echo PHP_EOL;

echo "=== SIMULATING getPaymentStatus() CONTROLLER LOGIC ===" . PHP_EOL;

if ($status['success']) {
    $currentPaymentStatus = $participant->payment_status;
    $newPaymentStatus = strtolower($status['status']);

    echo "Current payment_status: '{$currentPaymentStatus}'" . PHP_EOL;
    echo "New payment_status from Xendit: '{$newPaymentStatus}'" . PHP_EOL;

    if ($newPaymentStatus !== $currentPaymentStatus) {
        echo "Status changed - will update participant..." . PHP_EOL;

        $wasRegistered = $participant->status === 'registered';
        echo "Was already registered: " . ($wasRegistered ? 'Yes' : 'No') . PHP_EOL;

        $updateData = [
            'payment_status' => $newPaymentStatus,
            'is_paid' => $status['status'] === 'PAID',
            'amount_paid' => $status['paid_amount'] ?? $participant->amount_paid,
            'paid_at' => $status['status'] === 'PAID' ? now() : $participant->paid_at,
        ];

        if ($status['status'] === 'PAID' && $participant->status === 'pending_payment') {
            $updateData['status'] = 'registered';
            echo "✅ FIX APPLIED: Will update status from 'pending_payment' to 'registered'" . PHP_EOL;
        }

        $participant->update($updateData);
        echo "Participant updated successfully" . PHP_EOL;

        if ($status['status'] === 'PAID' && !$wasRegistered) {
            $oldCount = $participant->event->registered_count;
            $participant->event->increment('registered_count');
            $newCount = $participant->event->fresh()->registered_count;
            echo "✅ FIX APPLIED: Incremented registered_count from {$oldCount} to {$newCount}" . PHP_EOL;
        }
    } else {
        echo "No change in payment status - no update needed" . PHP_EOL;
    }
} else {
    echo "Failed to get payment status from Xendit" . PHP_EOL;
}
echo PHP_EOL;

// Reload participant to see updated values
$participant = $participant->fresh();

echo "=== AFTER STATUS CHECK ===" . PHP_EOL;
echo "Status: {$participant->status}" . PHP_EOL;
echo "Payment Status: {$participant->payment_status}" . PHP_EOL;
echo "Is Paid: " . ($participant->is_paid ? 'Yes' : 'No') . PHP_EOL;
echo "Amount Paid: " . ($participant->amount_paid ?? 'null') . PHP_EOL;
echo "Paid At: " . ($participant->paid_at ?? 'null') . PHP_EOL;
echo "Event registered_count: {$participant->event->fresh()->registered_count}" . PHP_EOL;
echo PHP_EOL;

echo "=== SUMMARY ===" . PHP_EOL;
if ($participant->status === 'registered' && $participant->is_paid) {
    echo "✅ SUCCESS: Participant is now registered and payment is confirmed!" . PHP_EOL;
} else if ($participant->status === 'pending_payment' && !$participant->is_paid) {
    echo "⏳ PENDING: Payment not yet completed on Xendit" . PHP_EOL;
} else {
    echo "⚠️ UNEXPECTED STATE: status={$participant->status}, is_paid=" . ($participant->is_paid ? 'true' : 'false') . PHP_EOL;
}
