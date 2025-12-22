<?php

namespace App\Services;

use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;

class WithdrawalService
{
    protected $balanceService;

    public function __construct(BalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    /**
     * Request withdrawal
     */
    public function requestWithdrawal(User $organizer, array $data): WithdrawalRequest
    {
        $amount = $data['amount'];

        // Validate minimum amount
        $minimumAmount = $this->balanceService->getMinimumWithdrawalAmount();
        if ($amount < $minimumAmount) {
            throw new \Exception("Minimum withdrawal amount is Rp " . number_format($minimumAmount, 0, ',', '.'));
        }

        // Check available balance
        if (!$this->balanceService->canWithdraw($organizer, $amount)) {
            throw new \Exception("Insufficient balance for withdrawal");
        }

        return DB::transaction(function () use ($organizer, $data, $amount) {
            // Get organizer balance
            $balance = $this->balanceService->getOrCreateBalance($organizer);
            $balanceBefore = $balance->available_balance;

            // Create withdrawal request
            $withdrawalRequest = WithdrawalRequest::create([
                'user_id' => $organizer->id,
                'amount' => $amount,
                'bank_name' => $data['bank_name'],
                'bank_account_number' => $data['bank_account_number'],
                'bank_account_holder' => $data['bank_account_holder'],
                'status' => 'pending',
                'requested_at' => now(),
            ]);

            // Update balance - add to pending withdrawal
            $balance->addPendingWithdrawal($amount);
            $balanceAfter = $balance->available_balance;

            // Create transaction log
            Transaction::create([
                'user_id' => $organizer->id,
                'withdrawal_request_id' => $withdrawalRequest->id,
                'type' => 'withdrawal_approved', // Actually pending, but we track it
                'amount' => -$amount, // Negative because it's going out
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => "Withdrawal request created to {$data['bank_name']} - {$data['bank_account_number']}",
                'metadata' => [
                    'withdrawal_request_id' => $withdrawalRequest->id,
                    'bank_name' => $data['bank_name'],
                    'bank_account_number' => $data['bank_account_number'],
                    'status' => 'pending',
                ],
            ]);

            Log::info('Withdrawal request created', [
                'organizer_id' => $organizer->id,
                'withdrawal_id' => $withdrawalRequest->id,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
            ]);

            return $withdrawalRequest;
        });
    }

    /**
     * Approve withdrawal
     */
    public function approveWithdrawal(WithdrawalRequest $request, User $admin, ?string $notes = null): void
    {
        if ($request->status !== 'pending') {
            throw new \Exception("Only pending withdrawal requests can be approved");
        }

        DB::transaction(function () use ($request, $admin, $notes) {
            $organizer = $request->organizer;
            $balance = $this->balanceService->getOrCreateBalance($organizer);
            $balanceBefore = $balance->available_balance;

            // Update withdrawal request status
            $request->update([
                'status' => 'approved',
                'approved_by' => $admin->id,
                'approved_at' => now(),
                'admin_notes' => $notes,
            ]);

            // Update balance - move from pending to withdrawn
            $balance->approvePendingWithdrawal($request->amount);
            $balanceAfter = $balance->available_balance;

            // Create transaction log
            Transaction::create([
                'user_id' => $organizer->id,
                'withdrawal_request_id' => $request->id,
                'type' => 'withdrawal_approved',
                'amount' => -$request->amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => "Withdrawal approved by {$admin->full_name} - {$request->bank_name} {$request->bank_account_number}",
                'metadata' => [
                    'withdrawal_request_id' => $request->id,
                    'approved_by' => $admin->id,
                    'admin_name' => $admin->full_name,
                    'admin_notes' => $notes,
                    'bank_name' => $request->bank_name,
                    'bank_account_number' => $request->bank_account_number,
                ],
            ]);

            Log::info('Withdrawal approved', [
                'organizer_id' => $organizer->id,
                'withdrawal_id' => $request->id,
                'amount' => $request->amount,
                'approved_by' => $admin->id,
            ]);

            // TODO: Send notification email to organizer
        });
    }

    /**
     * Reject withdrawal
     */
    public function rejectWithdrawal(WithdrawalRequest $request, User $admin, string $notes): void
    {
        if ($request->status !== 'pending') {
            throw new \Exception("Only pending withdrawal requests can be rejected");
        }

        DB::transaction(function () use ($request, $admin, $notes) {
            $organizer = $request->organizer;
            $balance = $this->balanceService->getOrCreateBalance($organizer);
            $balanceBefore = $balance->available_balance;

            // Update withdrawal request status
            $request->update([
                'status' => 'rejected',
                'approved_by' => $admin->id,
                'approved_at' => now(),
                'admin_notes' => $notes,
            ]);

            // Update balance - return pending to available
            $balance->rejectPendingWithdrawal($request->amount);
            $balanceAfter = $balance->available_balance;

            // Create transaction log
            Transaction::create([
                'user_id' => $organizer->id,
                'withdrawal_request_id' => $request->id,
                'type' => 'withdrawal_rejected',
                'amount' => 0, // Amount returned to available balance
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => "Withdrawal rejected by {$admin->full_name}: {$notes}",
                'metadata' => [
                    'withdrawal_request_id' => $request->id,
                    'rejected_by' => $admin->id,
                    'admin_name' => $admin->full_name,
                    'admin_notes' => $notes,
                    'bank_name' => $request->bank_name,
                    'amount_returned' => $request->amount,
                ],
            ]);

            Log::info('Withdrawal rejected', [
                'organizer_id' => $organizer->id,
                'withdrawal_id' => $request->id,
                'amount' => $request->amount,
                'rejected_by' => $admin->id,
                'reason' => $notes,
            ]);

            // TODO: Send notification email to organizer
        });
    }

    /**
     * Get withdrawal history with filters
     */
    public function getWithdrawalHistory(User $organizer, array $filters = []): Collection
    {
        $query = WithdrawalRequest::where('user_id', $organizer->id);

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('requested_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('requested_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('requested_at', 'desc')->get();
    }
}
