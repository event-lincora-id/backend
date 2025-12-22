<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WithdrawalService;
use App\Models\WithdrawalRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AdminWithdrawalController extends Controller
{
    protected $withdrawalService;

    public function __construct(WithdrawalService $withdrawalService)
    {
        $this->withdrawalService = $withdrawalService;
    }

    /**
     * Get all withdrawal requests (all organizers)
     * GET /api/admin/withdrawals
     */
    public function index(Request $request): JsonResponse
    {
        $query = WithdrawalRequest::with(['organizer', 'admin']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('organizer_id')) {
            $query->where('user_id', $request->organizer_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('requested_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('requested_at', '<=', $request->date_to);
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $withdrawals = $query->orderBy('requested_at', 'desc')->paginate($perPage);

        // Calculate summary statistics
        $summary = [
            'total_requests' => WithdrawalRequest::count(),
            'pending' => WithdrawalRequest::where('status', 'pending')->count(),
            'approved' => WithdrawalRequest::where('status', 'approved')->count(),
            'rejected' => WithdrawalRequest::where('status', 'rejected')->count(),
            'total_amount_pending' => WithdrawalRequest::where('status', 'pending')->sum('amount'),
            'total_amount_approved' => WithdrawalRequest::where('status', 'approved')->sum('amount'),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'withdrawals' => $withdrawals->items(),
                'pagination' => [
                    'current_page' => $withdrawals->currentPage(),
                    'last_page' => $withdrawals->lastPage(),
                    'per_page' => $withdrawals->perPage(),
                    'total' => $withdrawals->total(),
                    'from' => $withdrawals->firstItem(),
                    'to' => $withdrawals->lastItem(),
                ],
                'summary' => $summary,
                'filters' => [
                    'status' => $request->status,
                    'organizer_id' => $request->organizer_id,
                    'date_from' => $request->date_from,
                    'date_to' => $request->date_to,
                ]
            ]
        ]);
    }

    /**
     * Get specific withdrawal request details
     * GET /api/admin/withdrawals/{id}
     */
    public function show(WithdrawalRequest $withdrawal): JsonResponse
    {
        $withdrawal->load(['organizer', 'admin', 'organizer.organizerBalance']);

        return response()->json([
            'success' => true,
            'data' => [
                'withdrawal_request' => $withdrawal,
                'organizer_balance' => $withdrawal->organizer->organizerBalance,
            ]
        ]);
    }

    /**
     * Approve withdrawal request
     * POST /api/admin/withdrawals/{id}/approve
     */
    public function approve(Request $request, WithdrawalRequest $withdrawal): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $admin = $request->user();
            $this->withdrawalService->approveWithdrawal($withdrawal, $admin, $request->admin_notes);

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal request approved successfully',
                'data' => [
                    'withdrawal_request' => $withdrawal->fresh(['organizer', 'admin']),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve withdrawal request',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Reject withdrawal request
     * POST /api/admin/withdrawals/{id}/reject
     */
    public function reject(Request $request, WithdrawalRequest $withdrawal): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'admin_notes' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $admin = $request->user();
            $this->withdrawalService->rejectWithdrawal($withdrawal, $admin, $request->admin_notes);

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal request rejected successfully',
                'data' => [
                    'withdrawal_request' => $withdrawal->fresh(['organizer', 'admin']),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject withdrawal request',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
