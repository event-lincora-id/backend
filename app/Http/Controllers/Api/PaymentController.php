<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use App\Models\EventParticipant;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\InvoiceCreatedMail;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Create payment for event participation
     */
    public function createPayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'event_id' => 'required|exists:events,id',
            'payment_method' => 'required|in:invoice,virtual_account,ewallet',
            'bank_code' => 'required_if:payment_method,virtual_account|in:BCA,BNI,BRI,MANDIRI',
            'ewallet_type' => 'required_if:payment_method,ewallet|in:OVO,DANA,LINKAJA,SHOPEEPAY',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $event = Event::findOrFail($request->event_id);

        // Check if user is already participating
        $participant = EventParticipant::where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->first();

        // If participant exists, check their status
        if ($participant) {
            // User already registered or attended - cannot create new payment
            if (in_array($participant->status, ['registered', 'attended'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already joined this event'
                ], 422);
            }

            // User has pending payment - redirect to existing payment URL if available
            if ($participant->status === 'pending_payment' && $participant->payment_url) {
                return response()->json([
                    'success' => true,
                    'message' => 'You already have a pending payment for this event',
                    'data' => [
                        'payment_url' => $participant->payment_url,
                        'participant' => $participant,
                        'event' => [
                            'id' => $event->id,
                            'title' => $event->title,
                            'start_date' => $event->start_date,
                            'location' => $event->location,
                        ]
                    ]
                ]);
            }
        }

        if (!$participant) {
            // Auto-join the event first for paid events
            if ($event->price > 0) {
                // Check if event is full
                if ($event->registered_count >= $event->quota) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Event is full'
                    ], 400);
                }

                // Check if event has started
                if ($event->start_date < now()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Event has already started'
                    ], 400);
                }

                // Create participant record with PENDING_PAYMENT status
                // NOTE: User is NOT considered "registered" until payment is confirmed
                $participant = EventParticipant::create([
                    'user_id' => $user->id,
                    'event_id' => $event->id,
                    'status' => 'pending_payment',  // User not registered until payment confirmed
                    'is_paid' => false, // Will be updated after payment
                    'amount_paid' => null,
                    'payment_status' => 'pending',
                ]);

                // DON'T increment registered_count yet
                // Will be incremented when payment is confirmed via webhook
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not registered for this event'
                ], 400);
            }
        }

        // Check if already paid
        if ($participant->is_paid) {
            return response()->json([
                'success' => false,
                'message' => 'Payment already completed for this event'
            ], 400);
        }

        // Check if event is free
        if ($event->price <= 0) {
            $participant->update([
                'is_paid' => true,
                'payment_status' => 'paid',
                'amount_paid' => 0,
                'paid_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Event is free, registration completed',
                'data' => [
                    'participant' => $participant->fresh(),
                    'payment_url' => null,
                    'event' => [
                        'id' => $event->id,
                        'title' => $event->title,
                        'start_date' => $event->start_date,
                        'location' => $event->location,
                    ],
                    'attendance_qr' => [
                        'qr_code' => $participant->qr_code,
                        'qr_code_url' => $participant->qr_code ? asset('storage/' . $participant->qr_code) : null,
                        'qr_code_string' => $participant->qr_code_string,
                        'message' => 'Use this QR code for attendance check-in at the event'
                    ]
                ]
            ]);
        }

        // Create payment based on method
        $result = null;
        switch ($request->payment_method) {
            case 'invoice':
                $result = $this->paymentService->createInvoice($participant);
                break;
            case 'virtual_account':
                $result = $this->paymentService->createVirtualAccount($participant, $request->bank_code);
                break;
            case 'ewallet':
                $result = $this->paymentService->createEWalletPayment($participant, $request->ewallet_type);
                break;
        }

        if (!$result || !$result['success']) {
            // Log the error for debugging
            Log::error('Payment creation failed', [
                'participant_id' => $participant->id,
                'event_id' => $event->id,
                'payment_method' => $request->payment_method,
                'error' => $result['error'] ?? 'Unknown error',
                'result' => $result
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment creation failed: ' . ($result['error'] ?? 'Unknown error'),
                'error' => $result['error'] ?? 'Unknown error',
                // Include payment_url if available even on error
                'data' => [
                    'payment_url' => $result['payment_url'] ?? $result['invoice_url'] ?? null,
                ]
            ], 500);
        }

        // Send invoice email for invoice payment method
        if ($request->payment_method === 'invoice') {
            $invoiceUrl = $result['payment_url'] ?? $result['invoice_url'] ?? null;
            if ($invoiceUrl) {
                try {
                    Mail::to($user->email)->send(new InvoiceCreatedMail($event, $user, $participant, $invoiceUrl));
                } catch (\Exception $e) {
                    Log::error('Failed to send invoice email', [
                        'participant_id' => $participant->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment created successfully',
            'data' => [
                'participant' => $participant->fresh(),
                'payment_url' => $result['payment_url'] ?? $result['checkout_url'] ?? $result['account_number'],
                'payment_reference' => $result['invoice_id'] ?? $result['va_id'] ?? $result['ewallet_id'],
                'payment_method' => $request->payment_method,
                'event' => [
                    'id' => $event->id,
                    'title' => $event->title,
                    'start_date' => $event->start_date,
                    'location' => $event->location,
                ],
                'attendance_qr' => [
                    'qr_code' => $participant->qr_code,
                    'qr_code_url' => $participant->qr_code ? asset('storage/' . $participant->qr_code) : null,
                    'qr_code_string' => $participant->qr_code_string,
                    'message' => 'Use this QR code for attendance check-in at the event'
                ]
            ]
        ]);
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus(Request $request, EventParticipant $participant): JsonResponse
    {
        $user = $request->user();

        // Check if user owns this participation
        if ($participant->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        // If payment reference exists, get status from Xendit
        if ($participant->payment_reference) {
            $status = $this->paymentService->getPaymentStatus($participant->payment_reference);

            if ($status['success']) {
                // Update local status if different
                $currentPaymentStatus = $participant->payment_status;
                $newPaymentStatus = strtolower($status['status']);

                if ($newPaymentStatus !== $currentPaymentStatus) {
                    // Check if payment just completed
                    $wasRegistered = $participant->status === 'registered';

                    // Prepare update data
                    $updateData = [
                        'payment_status' => $newPaymentStatus,
                        'is_paid' => $status['status'] === 'PAID',
                        'amount_paid' => ($status['paid_amount'] > 0) ? $status['paid_amount'] : $participant->event->price,
                        'paid_at' => $status['status'] === 'PAID' ? now() : $participant->paid_at,
                    ];

                    // CRITICAL FIX: Update participant status when payment confirmed
                    if ($status['status'] === 'PAID' && $participant->status === 'pending_payment') {
                        $updateData['status'] = 'registered';

                        Log::info('Payment confirmed via status check - registering participant', [
                            'participant_id' => $participant->id,
                            'event_id' => $participant->event_id,
                            'payment_reference' => $participant->payment_reference,
                        ]);
                    }

                    $participant->update($updateData);

                    // CRITICAL FIX: Increment registered count when payment confirmed
                    if ($status['status'] === 'PAID' && !$wasRegistered) {
                        $participant->event->increment('registered_count');

                        Log::info('Incremented event registered_count via status check', [
                            'event_id' => $participant->event_id,
                            'new_count' => $participant->event->fresh()->registered_count,
                        ]);
                    }
                }
            }
        }

        $event = $participant->event;
        
        return response()->json([
            'success' => true,
            'data' => [
                'participant' => $participant->fresh(),
                'payment_status' => $participant->payment_status,
                'is_paid' => $participant->is_paid,
                'amount_paid' => $participant->amount_paid,
                'paid_at' => $participant->paid_at,
                'payment_url' => $participant->payment_url,
                'event' => [
                    'id' => $event->id,
                    'title' => $event->title,
                    'start_date' => $event->start_date,
                    'location' => $event->location,
                ],
                'attendance_qr' => [
                    'qr_code' => $participant->qr_code,
                    'qr_code_url' => $participant->qr_code ? asset('storage/' . $participant->qr_code) : null,
                    'qr_code_string' => $participant->qr_code_string,
                    'message' => 'Use this QR code for attendance check-in at the event'
                ]
            ]
        ]);
    }

    /**
     * Handle Xendit webhook
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Xendit-Signature');

        // Verify webhook signature (skip if null - typical in staging/test environment)
        if ($signature !== null && !$this->paymentService->verifyWebhookSignature($payload, $signature)) {
            Log::warning('Invalid webhook signature', [
                'signature' => $signature,
                'payload' => $payload,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid signature'
            ], 401);
        }

        // Log if signature verification was skipped
        if ($signature === null) {
            Log::info('Webhook signature verification skipped (staging/test mode)');
        }

        $webhookData = json_decode($payload, true);

        Log::info('Xendit webhook received', [
            'external_id' => $webhookData['external_id'] ?? 'unknown',
            'status' => $webhookData['status'] ?? 'unknown',
        ]);

        // Process webhook
        $result = $this->paymentService->handleWebhook($webhookData);

        if ($result) {
            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed'
            ], 500);
        }
    }

    /**
     * Get available payment methods
     */
    public function getPaymentMethods(): JsonResponse
    {
        $methods = $this->paymentService->getAvailablePaymentMethods();

        return response()->json([
            'success' => true,
            'data' => $methods
        ]);
    }

    /**
     * Cancel payment
     */
    public function cancelPayment(Request $request, EventParticipant $participant): JsonResponse
    {
        $user = $request->user();

        // Check if user owns this participation
        if ($participant->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        // Check if payment can be cancelled
        if ($participant->is_paid) {
            return response()->json([
                'success' => false,
                'message' => 'Payment already completed, cannot cancel'
            ], 400);
        }

        if ($participant->payment_status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Payment already cancelled'
            ], 400);
        }

        // Update payment status
        $participant->update([
            'payment_status' => 'cancelled',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment cancelled successfully',
            'data' => [
                'participant' => $participant->fresh(),
            ]
        ]);
    }

    /**
     * Retry payment
     */
    public function retryPayment(Request $request, EventParticipant $participant): JsonResponse
    {
        $user = $request->user();

        // Check if user owns this participation
        if ($participant->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        // Check if payment can be retried
        if ($participant->is_paid) {
            return response()->json([
                'success' => false,
                'message' => 'Payment already completed'
            ], 400);
        }

        if (!in_array($participant->payment_status, ['failed', 'expired', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'Payment cannot be retried in current status'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|in:invoice,virtual_account,ewallet',
            'bank_code' => 'required_if:payment_method,virtual_account|in:BCA,BNI,BRI,MANDIRI',
            'ewallet_type' => 'required_if:payment_method,ewallet|in:OVO,DANA,LINKAJA,SHOPEEPAY',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // Create new payment
        $result = null;
        switch ($request->payment_method) {
            case 'invoice':
                $result = $this->paymentService->createInvoice($participant);
                break;
            case 'virtual_account':
                $result = $this->paymentService->createVirtualAccount($participant, $request->bank_code);
                break;
            case 'ewallet':
                $result = $this->paymentService->createEWalletPayment($participant, $request->ewallet_type);
                break;
        }

        if (!$result || !$result['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Payment retry failed',
                'error' => $result['error'] ?? 'Unknown error'
            ], 500);
        }

        // Send invoice email for invoice payment method
        if ($request->payment_method === 'invoice') {
            $invoiceUrl = $result['payment_url'] ?? $result['invoice_url'] ?? null;
            if ($invoiceUrl) {
                try {
                    Mail::to($user->email)->send(new InvoiceCreatedMail($participant->event, $user, $participant, $invoiceUrl));
                } catch (\Exception $e) {
                    Log::error('Failed to send invoice email on retry', [
                        'participant_id' => $participant->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment retry created successfully',
            'data' => [
                'participant' => $participant->fresh(),
                'payment_url' => $result['payment_url'] ?? $result['checkout_url'] ?? $result['account_number'],
                'payment_reference' => $result['invoice_id'] ?? $result['va_id'] ?? $result['ewallet_id'],
                'payment_method' => $request->payment_method,
            ]
        ]);
    }

    /**
     * Get payment history for authenticated user
     */
    public function getPaymentHistory(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Get query parameters
        $status = $request->get('status'); // paid, pending, failed, cancelled
        $paymentMethod = $request->get('payment_method'); // invoice, virtual_account, ewallet
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $perPage = $request->get('per_page', 10);
        
        // Build query
        $query = EventParticipant::with(['event.organizer', 'event.category'])
            ->where('user_id', $user->id)
            ->whereNotNull('payment_reference'); // Only participants with payment records
        
        // Apply filters
        if ($status) {
            $query->where('payment_status', $status);
        }
        
        if ($paymentMethod) {
            $query->where('payment_method', $paymentMethod);
        }
        
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }
        
        // Order by most recent first
        $query->orderBy('created_at', 'desc');
        
        // Paginate results
        $payments = $query->paginate($perPage);
        
        // Transform data for response
        $transformedPayments = $payments->getCollection()->map(function ($participant) {
            return [
                'id' => $participant->id,
                'event' => [
                    'id' => $participant->event->id,
                    'title' => $participant->event->title,
                    'start_date' => $participant->event->start_date,
                    'location' => $participant->event->location,
                    'price' => $participant->event->price,
                    'organizer' => [
                        'id' => $participant->event->organizer->id,
                        'name' => $participant->event->organizer->full_name ?? $participant->event->organizer->name,
                        'email' => $participant->event->organizer->email,
                    ],
                    'category' => [
                        'id' => $participant->event->category->id,
                        'name' => $participant->event->category->name,
                        'color' => $participant->event->category->color,
                    ]
                ],
                'payment' => [
                    'reference' => $participant->payment_reference,
                    'method' => $participant->payment_method,
                    'status' => $participant->payment_status,
                    'amount' => $participant->amount_paid,
                    'is_paid' => $participant->is_paid,
                    'paid_at' => $participant->paid_at,
                    'created_at' => $participant->created_at,
                    'updated_at' => $participant->updated_at,
                ],
                'participation' => [
                    'status' => $participant->status,
                    'attended_at' => $participant->attended_at,
                    'qr_code' => $participant->qr_code,
                    'qr_code_url' => $participant->qr_code ? asset('storage/' . $participant->qr_code) : null,
                ]
            ];
        });
        
        // Get summary statistics
        $summary = [
            'total_payments' => EventParticipant::where('user_id', $user->id)
                ->whereNotNull('payment_reference')
                ->count(),
            'total_paid' => EventParticipant::where('user_id', $user->id)
                ->where('is_paid', true)
                ->sum('amount_paid'),
            'total_pending' => EventParticipant::where('user_id', $user->id)
                ->where('payment_status', 'pending')
                ->count(),
            'total_failed' => EventParticipant::where('user_id', $user->id)
                ->where('payment_status', 'failed')
                ->count(),
        ];
        
        return response()->json([
            'success' => true,
            'data' => [
                'payments' => $transformedPayments,
                'pagination' => [
                    'current_page' => $payments->currentPage(),
                    'last_page' => $payments->lastPage(),
                    'per_page' => $payments->perPage(),
                    'total' => $payments->total(),
                    'from' => $payments->firstItem(),
                    'to' => $payments->lastItem(),
                    'has_more_pages' => $payments->hasMorePages(),
                ],
                'summary' => $summary,
                'filters' => [
                    'status' => $status,
                    'payment_method' => $paymentMethod,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                ]
            ]
        ]);
    }

    /**
     * Get payment statistics for authenticated user
     */
    public function getPaymentStatistics(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Get date range (default: last 12 months)
        $dateFrom = $request->get('date_from', now()->subMonths(12)->startOfMonth());
        $dateTo = $request->get('date_to', now()->endOfMonth());
        
        // Base query
        $baseQuery = EventParticipant::where('user_id', $user->id)
            ->whereNotNull('payment_reference')
            ->whereBetween('created_at', [$dateFrom, $dateTo]);
        
        // Payment status breakdown
        $statusBreakdown = $baseQuery->clone()
            ->selectRaw('payment_status, COUNT(*) as count, SUM(amount_paid) as total_amount')
            ->groupBy('payment_status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->payment_status => [
                    'count' => $item->count,
                    'total_amount' => $item->total_amount ?? 0
                ]];
            });
        
        // Payment method breakdown
        $methodBreakdown = $baseQuery->clone()
            ->selectRaw('payment_method, COUNT(*) as count, SUM(amount_paid) as total_amount')
            ->whereNotNull('payment_method')
            ->groupBy('payment_method')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->payment_method => [
                    'count' => $item->count,
                    'total_amount' => $item->total_amount ?? 0
                ]];
            });
        
        // Monthly payment trends
        $monthlyTrends = $baseQuery->clone()
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count, SUM(amount_paid) as total_amount')
            ->groupBy('month')
            ->orderBy('month')
            ->get();
        
        // Event category breakdown
        $categoryBreakdown = $baseQuery->clone()
            ->join('events', 'event_participants.event_id', '=', 'events.id')
            ->join('categories', 'events.category_id', '=', 'categories.id')
            ->selectRaw('categories.name as category_name, COUNT(*) as count, SUM(event_participants.amount_paid) as total_amount')
            ->where('event_participants.user_id', $user->id)
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('total_amount', 'desc')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'from' => $dateFrom,
                    'to' => $dateTo
                ],
                'status_breakdown' => $statusBreakdown,
                'method_breakdown' => $methodBreakdown,
                'monthly_trends' => $monthlyTrends,
                'category_breakdown' => $categoryBreakdown,
                'summary' => [
                    'total_payments' => $baseQuery->count(),
                    'total_amount' => $baseQuery->sum('amount_paid'),
                    'average_amount' => $baseQuery->avg('amount_paid'),
                    'success_rate' => $baseQuery->where('is_paid', true)->count() / max($baseQuery->count(), 1) * 100
                ]
            ]
        ]);
    }
}