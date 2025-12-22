<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BalanceService;
use App\Services\WithdrawalService;
use App\Models\WithdrawalRequest;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class WithdrawalController extends Controller
{
    protected $balanceService;
    protected $withdrawalService;

    public function __construct(BalanceService $balanceService, WithdrawalService $withdrawalService)
    {
        $this->balanceService = $balanceService;
        $this->withdrawalService = $withdrawalService;
    }

    /**
     * Request withdrawal
     * POST /api/withdrawals/request
     */
    public function requestWithdrawal(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
            'bank_name' => 'required|string|max:255',
            'bank_account_number' => 'required|string|max:255',
            'bank_account_holder' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $withdrawalRequest = $this->withdrawalService->requestWithdrawal($user, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal request created successfully',
                'data' => [
                    'withdrawal_request' => $withdrawalRequest,
                    'balance' => $this->balanceService->getOrCreateBalance($user),
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(), // Use the specific error message
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get withdrawal history
     * GET /api/withdrawals/history
     */
    public function getHistory(Request $request): JsonResponse
    {
        $user = $request->user();

        $filters = [
            'status' => $request->get('status'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
        ];

        // Remove null filters
        $filters = array_filter($filters, function($value) {
            return $value !== null;
        });

        $withdrawals = $this->withdrawalService->getWithdrawalHistory($user, $filters);

        return response()->json([
            'success' => true,
            'data' => [
                'withdrawals' => $withdrawals->load('admin'),
                'summary' => [
                    'total_requests' => $withdrawals->count(),
                    'pending' => $withdrawals->where('status', 'pending')->count(),
                    'approved' => $withdrawals->where('status', 'approved')->count(),
                    'rejected' => $withdrawals->where('status', 'rejected')->count(),
                    'total_withdrawn' => $withdrawals->where('status', 'approved')->sum('amount'),
                ]
            ]
        ]);
    }

    /**
     * Get specific withdrawal request details
     * GET /api/withdrawals/{id}
     */
    public function show(Request $request, WithdrawalRequest $withdrawal): JsonResponse
    {
        $user = $request->user();

        // Check if user owns this withdrawal request
        if ($withdrawal->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'withdrawal_request' => $withdrawal->load('admin'),
            ]
        ]);
    }

    /**
     * Get balance dashboard
     * GET /api/balance/dashboard
     */
    public function getBalanceDashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $balance = $this->balanceService->getOrCreateBalance($user);

        // Get recent transactions (last 10)
        $recentTransactions = Transaction::where('user_id', $user->id)
            ->with(['event', 'withdrawalRequest'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get pending withdrawals
        $pendingWithdrawals = WithdrawalRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->orderBy('requested_at', 'desc')
            ->get();

        // Calculate paid registrations and pending payments
        $paidRegistrations = Transaction::where('user_id', $user->id)
            ->where('type', 'payment_received')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'balance' => [
                    'total_earned' => $balance->total_earned,
                    'available_balance' => $balance->available_balance,
                    'withdrawn' => $balance->withdrawn,
                    'pending_withdrawal' => $balance->pending_withdrawal,
                    'platform_fee_total' => $balance->platform_fee_total,
                ],
                'recent_transactions' => $recentTransactions,
                'pending_withdrawals' => $pendingWithdrawals,
                'statistics' => [
                    'total_transactions' => Transaction::where('user_id', $user->id)->count(),
                    'paid_registrations' => $paidRegistrations,
                    'total_withdrawal_requests' => WithdrawalRequest::where('user_id', $user->id)->count(),
                    'approved_withdrawals' => WithdrawalRequest::where('user_id', $user->id)->where('status', 'approved')->count(),
                    'platform_fee_percentage' => $this->balanceService->getPlatformFeePercentage(),
                    'minimum_withdrawal_amount' => $this->balanceService->getMinimumWithdrawalAmount(),
                ]
            ]
        ]);
    }
}
