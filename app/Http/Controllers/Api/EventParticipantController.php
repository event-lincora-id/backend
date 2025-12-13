<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class EventParticipantController extends Controller
{
    /**
     * Join an event
     */
    public function joinEvent(Request $request, Event $event): JsonResponse
    {
        $user = $request->user();

        // Check if event is published and active
        if ($event->status !== 'published' || !$event->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Event is not available for registration'
            ], 400);
        }

        // Check if event is full
        if ($event->is_full) {
            return response()->json([
                'success' => false,
                'message' => 'Event is full'
            ], 400);
        }

        // Check if user already registered
        $existingParticipant = EventParticipant::where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->first();

        if ($existingParticipant) {
            return response()->json([
                'success' => false,
                'message' => 'You are already registered for this event'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Generate unique QR code for this user and event
            $qrCodeString = 'user_' . $user->id . '_event_' . $event->id . '_' . time() . '_' . uniqid();
            $qrCodePath = 'qr_codes/participants/' . $qrCodeString . '.svg';
            
            // Create directory if it doesn't exist
            $qrCodeDir = storage_path('app/public/qr_codes/participants');
            if (!is_dir($qrCodeDir)) {
                mkdir($qrCodeDir, 0755, true);
            }
            
            // Generate QR code image
            \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(200)->generate($qrCodeString, storage_path('app/public/' . $qrCodePath));
            
            // Create participant record
            $participant = EventParticipant::create([
                'user_id' => $user->id,
                'event_id' => $event->id,
                'status' => 'registered',
                'is_paid' => $event->price <= 0, // Only free events are automatically paid
                'amount_paid' => $event->price <= 0 ? 0 : null,
                'payment_status' => $event->price <= 0 ? 'paid' : 'pending',
                'qr_code' => $qrCodePath,
                'qr_code_string' => $qrCodeString,
            ]);

            // Update event registered count
            $event->increment('registered_count');

            // Create notification for organizer
            Notification::create([
                'user_id' => $event->user_id,
                'event_id' => $event->id,
                'type' => 'event_registration',
                'title' => 'New Event Registration',
                'message' => $user->full_name . ' has registered for your event: ' . $event->title,
                'data' => [
                    'participant_id' => $participant->id,
                    'participant_name' => $user->full_name
                ]
            ]);

            DB::commit();

            // Prepare response data with user-specific QR code
            $responseData = [
                'participant' => $participant,
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
            ];

            return response()->json([
                'success' => true,
                'message' => $event->is_paid ? 'Registration successful. Please complete payment.' : 'Successfully joined the event',
                'data' => $responseData
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to join event'
            ], 500);
        }
    }

    /**
     * Cancel event participation
     */
    public function cancelParticipation(Request $request, Event $event): JsonResponse
    {
        $user = $request->user();

        $participant = EventParticipant::where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->first();

        if (!$participant) {
            return response()->json([
                'success' => false,
                'message' => 'You are not registered for this event'
            ], 400);
        }

        if ($participant->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Participation already cancelled'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $participant->update(['status' => 'cancelled']);
            $event->decrement('registered_count');

            // Create notification for organizer
            Notification::create([
                'user_id' => $event->user_id,
                'event_id' => $event->id,
                'type' => 'event_cancellation',
                'title' => 'Event Registration Cancelled',
                'message' => $user->full_name . ' has cancelled their registration for: ' . $event->title,
                'data' => [
                    'participant_id' => $participant->id,
                    'participant_name' => $user->full_name
                ]
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Participation cancelled successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel participation'
            ], 500);
        }
    }

    /**
     * Mark attendance using QR code
     */
    public function markAttendance(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'qr_code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $qrCodeString = $request->qr_code;

        // Determine QR type: Event QR or Participant QR
        $isEventQR = str_starts_with($qrCodeString, 'event_');
        $participant = null;

        if ($isEventQR) {
            // Event QR: Find event by QR code, then find participant by user_id + event_id
            $event = Event::where('qr_code_string', $qrCodeString)->first();

            if (!$event) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid event QR code'
                ], 404);
            }

            // Find participant record for this user + event
            $participant = EventParticipant::where('user_id', $user->id)
                                           ->where('event_id', $event->id)
                                           ->first();

            if (!$participant) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not registered for this event'
                ], 404);
            }

        } else {
            // Participant QR: Original logic (backward compatibility)
            $participant = EventParticipant::where('qr_code_string', $qrCodeString)
                                           ->where('user_id', $user->id)
                                           ->first();

            if (!$participant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid QR code or you are not authorized to use this QR code'
                ], 404);
            }
        }

        // Check if already attended
        if ($participant->status === 'attended') {
            return response()->json([
                'success' => false,
                'message' => 'You have already marked your attendance for this event',
                'data' => [
                    'attended_at' => $participant->attended_at
                ]
            ], 400);
        }

        // Check if participant is registered (not pending_payment or cancelled)
        if (!in_array($participant->status, ['registered'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot mark attendance. Your registration status is: ' . $participant->status
            ], 400);
        }

        // Mark as attended
        $participant->update([
            'status' => 'attended',
            'attended_at' => now(),
        ]);

        \Log::info('Attendance marked', [
            'user_id' => $user->id,
            'event_id' => $participant->event_id,
            'participant_id' => $participant->id,
            'qr_type' => $isEventQR ? 'event_qr' : 'participant_qr'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Attendance marked successfully',
            'data' => [
                'event' => $participant->event->title ?? 'Event',
                'attended_at' => $participant->attended_at,
                'participant' => $participant
            ]
        ]);
    }

    /**
     * Get user's event participations
     */
    public function myParticipations(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = EventParticipant::with(['event.organizer', 'event.category'])
            ->where('user_id', $user->id);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $participations = $query->orderBy('created_at', 'desc')->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $participations
        ]);
    }

    /**
     * Get event participants (Organizer only)
     */
    public function getEventParticipants(Request $request, Event $event): JsonResponse
    {
        $user = $request->user();

        if ($event->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view participants'
            ], 403);
        }

        $participants = EventParticipant::with(['user'])
            ->where('event_id', $event->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $participants
        ]);
    }

    /**
     * Process payment for paid events
     */
    public function processPayment(Request $request, Event $event): JsonResponse
    {
        $user = $request->user();

        $participant = EventParticipant::where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->first();

        if (!$participant) {
            return response()->json([
                'success' => false,
                'message' => 'You are not registered for this event'
            ], 400);
        }

        if ($participant->is_paid) {
            return response()->json([
                'success' => false,
                'message' => 'Payment already processed'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'payment_reference' => 'required|string',
            'amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify payment amount matches event price
        if ($request->amount != $event->price) {
            return response()->json([
                'success' => false,
                'message' => 'Payment amount does not match event price'
            ], 400);
        }

        $participant->update([
            'is_paid' => true,
            'amount_paid' => $request->amount,
            'payment_reference' => $request->payment_reference
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment processed successfully',
            'data' => $participant
        ]);
    }

    /**
     * Cancel payment by participant ID (Option C: Mark as cancelled, keep record)
     */
    public function cancel(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        $participant = EventParticipant::find($id);

        if (!$participant) {
            return response()->json([
                'success' => false,
                'message' => 'Participant not found'
            ], 404);
        }

        // Check authorization - only participant owner or admin can cancel
        if ($participant->user_id !== $user->id && !$user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to cancel this payment'
            ], 403);
        }

        // Only allow cancellation if not paid
        if ($participant->is_paid || $participant->payment_status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel paid registration'
            ], 400);
        }

        // Mark as cancelled (Option C: keep record for history)
        $participant->update([
            'status' => 'cancelled',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment cancelled successfully'
        ]);
    }
}
