<?php

namespace App\Services;

use App\Models\User;
use App\Models\OrganizerBalance;
use App\Models\EventParticipant;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BalanceService
{
    /**
     * Get or create organizer balance
     */
    public function getOrCreateBalance(User $organizer): OrganizerBalance
    {
        return OrganizerBalance::firstOrCreate(
            ['user_id' => $organizer->id],
            [
                'total_earned' => 0,
                'withdrawn' => 0,
                'pending_withdrawal' => 0,
                'available_balance' => 0,
                'platform_fee_total' => 0,
            ]
        );
    }

    /**
     * Add payment to organizer balance (called when payment is confirmed)
     */
    public function addPaymentToBalance(EventParticipant $participant): void
    {
        DB::transaction(function () use ($participant) {
            $event = $participant->event;
            $organizer = $event->organizer;

            // Get or create organizer balance
            $balance = $this->getOrCreateBalance($organizer);

            // Calculate platform fee
            $paymentAmount = $participant->amount_paid;
            $platformFee = $this->calculatePlatformFee($paymentAmount);
            $netAmount = $paymentAmount - $platformFee;

            $balanceBefore = $balance->available_balance;

            // Add earnings to balance
            $balance->addEarnings($netAmount, $platformFee);

            $balanceAfter = $balance->available_balance;

            // Log payment received transaction
            Transaction::create([
                'user_id' => $organizer->id,
                'event_id' => $event->id,
                'participant_id' => $participant->id,
                'type' => 'payment_received',
                'amount' => $netAmount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => "Payment received from {$participant->user->full_name} for event: {$event->title}",
                'metadata' => [
                    'gross_amount' => $paymentAmount,
                    'platform_fee' => $platformFee,
                    'net_amount' => $netAmount,
                    'payment_reference' => $participant->payment_reference,
                ],
            ]);

            // Log platform fee transaction
            Transaction::create([
                'user_id' => $organizer->id,
                'event_id' => $event->id,
                'participant_id' => $participant->id,
                'type' => 'platform_fee',
                'amount' => $platformFee,
                'balance_before' => $balanceAfter,
                'balance_after' => $balanceAfter,
                'description' => "Platform fee (" . $this->getPlatformFeePercentage() . "%) for payment",
                'metadata' => [
                    'gross_amount' => $paymentAmount,
                    'platform_fee_percentage' => $this->getPlatformFeePercentage(),
                ],
            ]);

            Log::info('Payment added to organizer balance', [
                'organizer_id' => $organizer->id,
                'event_id' => $event->id,
                'participant_id' => $participant->id,
                'gross_amount' => $paymentAmount,
                'platform_fee' => $platformFee,
                'net_amount' => $netAmount,
                'new_balance' => $balance->available_balance,
            ]);
        });
    }

    /**
     * Get available balance for withdrawal
     */
    public function getAvailableBalance(User $organizer): float
    {
        $balance = $this->getOrCreateBalance($organizer);
        return (float) $balance->available_balance;
    }

    /**
     * Check if organizer can withdraw amount
     */
    public function canWithdraw(User $organizer, float $amount): bool
    {
        $availableBalance = $this->getAvailableBalance($organizer);
        $minimumAmount = $this->getMinimumWithdrawalAmount();

        return $amount >= $minimumAmount && $amount <= $availableBalance;
    }

    /**
     * Calculate platform fee based on config percentage
     */
    public function calculatePlatformFee(float $amount): float
    {
        $percentage = $this->getPlatformFeePercentage();
        return round($amount * ($percentage / 100), 2);
    }

    /**
     * Get platform fee percentage from config
     */
    public function getPlatformFeePercentage(): float
    {
        return (float) config('services.xendit.platform_fee_percentage', 5);
    }

    /**
     * Get minimum withdrawal amount from config
     */
    public function getMinimumWithdrawalAmount(): float
    {
        return (float) config('services.xendit.minimum_withdrawal_amount', 50000);
    }
}
