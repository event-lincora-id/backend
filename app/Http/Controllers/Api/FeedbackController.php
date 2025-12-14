<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Feedback;
use App\Models\EventParticipant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * @OA\Tag(
 *     name="Feedback",
 *     description="API Endpoints untuk manajemen feedback event"
 * )
 */
class FeedbackController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/feedbacks/{event}",
     *     tags={"Feedback"},
     *     summary="Submit feedback untuk event",
     *     description="User yang sudah attend event dapat memberikan feedback dan mendapatkan sertifikat",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="event",
     *         in="path",
     *         description="ID Event",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"rating","comment"},
     *             @OA\Property(property="rating", type="integer", example=5, description="Rating 1-5"),
     *             @OA\Property(property="comment", type="string", example="Event sangat bermanfaat dan terorganisir dengan baik", maxLength=1000)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Feedback berhasil disubmit",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Feedback submitted successfully. Certificate generated."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="event_id", type="integer", example=1),
     *                 @OA\Property(property="rating", type="integer", example=5),
     *                 @OA\Property(property="comment", type="string", example="Event sangat bermanfaat"),
     *                 @OA\Property(property="certificate_generated", type="boolean", example=true),
     *                 @OA\Property(property="certificate_path", type="string", example="certificates/certificate_1_1_1234567890.pdf")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="User belum attend event atau event belum selesai"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request, Event $event): JsonResponse
    {
        $user = $request->user();

        $participant = EventParticipant::where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->where('status', 'attended')
            ->first();

        if (!$participant) {
            return response()->json([
                'success' => false,
                'message' => 'You must attend the event to submit feedback'
            ], 400);
        }

        if ($event->end_date > now()) {
            return response()->json([
                'success' => false,
                'message' => 'Event has not ended yet'
            ], 400);
        }

        $existingFeedback = Feedback::where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->first();

        if ($existingFeedback) {
            return response()->json([
                'success' => false,
                'message' => 'Feedback already submitted for this event'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $feedback = Feedback::create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        $this->generateCertificate($feedback);

        return response()->json([
            'success' => true,
            'message' => 'Feedback submitted successfully. Certificate generated.',
            'data' => $feedback
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/feedbacks/my-feedbacks",
     *     tags={"Feedback"},
     *     summary="Get daftar feedback user",
     *     description="Mendapatkan semua feedback yang pernah diberikan user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Nomor halaman",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Daftar feedback berhasil didapatkan",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="rating", type="integer", example=5),
     *                         @OA\Property(property="comment", type="string", example="Event sangat bagus"),
     *                         @OA\Property(property="certificate_generated", type="boolean", example=true),
     *                         @OA\Property(property="event", type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="title", type="string", example="Tech Conference 2024")
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="total", type="integer", example=50)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function myFeedbacks(Request $request): JsonResponse
    {
        $user = $request->user();

        $feedbacks = Feedback::with(['event.organizer', 'event.category'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $feedbacks
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/feedbacks/event/{event}",
     *     tags={"Feedback"},
     *     summary="Get feedback untuk event tertentu (Organizer only)",
     *     description="Organizer dapat melihat semua feedback untuk event mereka",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="event",
     *         in="path",
     *         description="ID Event",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Nomor halaman",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Daftar feedback event",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="rating", type="integer", example=5),
     *                         @OA\Property(property="comment", type="string", example="Event sangat bagus"),
     *                         @OA\Property(property="user", type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="name", type="string", example="John Doe")
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(property="per_page", type="integer", example=20)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Unauthorized - Bukan organizer event"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getEventFeedbacks(Request $request, Event $event): JsonResponse
    {
        $user = $request->user();

        if ($event->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view feedbacks'
            ], 403);
        }

        $feedbacks = Feedback::with(['user'])
            ->where('event_id', $event->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $feedbacks
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/feedbacks/certificate/{event}/download",
     *     tags={"Feedback"},
     *     summary="Download sertifikat event",
     *     description="Download file PDF sertifikat",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="event",
     *         in="path",
     *         description="ID Event",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File PDF sertifikat",
     *         @OA\MediaType(
     *             mediaType="application/pdf"
     *         )
     *     ),
     *     @OA\Response(response=400, description="Certificate belum di-generate"),
     *     @OA\Response(response=404, description="Feedback atau file tidak ditemukan"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function downloadCertificate(Request $request, Event $event)
    {
        $user = $request->user();

        $feedback = Feedback::where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->first();

        if (!$feedback) {
            return response()->json([
                'success' => false,
                'message' => 'Feedback not found'
            ], 404);
        }

        if (!$feedback->certificate_generated) {
            return response()->json([
                'success' => false,
                'message' => 'Certificate not generated yet'
            ], 400);
        }

        $certificatePath = storage_path('app/public/' . $feedback->certificate_path);

        if (!file_exists($certificatePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Certificate file not found'
            ], 404);
        }

        return response()->download($certificatePath, 'certificate_' . $event->title . '.pdf');
    }

    /**
     * @OA\Get(
     *     path="/api/feedbacks/certificate/{event}/url",
     *     tags={"Feedback"},
     *     summary="Get URL sertifikat event",
     *     description="Mendapatkan public URL untuk mengakses sertifikat",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="event",
     *         in="path",
     *         description="ID Event",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="URL sertifikat berhasil didapatkan",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="certificate_url", type="string", example="http://localhost/storage/certificates/certificate_1_1_1234567890.pdf")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Certificate belum di-generate"),
     *     @OA\Response(response=404, description="Feedback tidak ditemukan"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getCertificateUrl(Request $request, Event $event): JsonResponse
    {
        $user = $request->user();

        $feedback = Feedback::where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->first();

        if (!$feedback) {
            return response()->json([
                'success' => false,
                'message' => 'Feedback not found'
            ], 404);
        }

        if (!$feedback->certificate_generated) {
            return response()->json([
                'success' => false,
                'message' => 'Certificate not generated yet'
            ], 400);
        }

        $certificateUrl = asset('storage/' . $feedback->certificate_path);

        return response()->json([
            'success' => true,
            'data' => [
                'certificate_url' => $certificateUrl
            ]
        ]);
    }

    private function generateCertificate(Feedback $feedback): void
    {
        $event = $feedback->event;
        $user = $feedback->user;

        $filename = 'certificate_' . $event->id . '_' . $user->id . '_' . time() . '.pdf';
        $certificatePath = 'certificates/' . $filename;

        $pdf = Pdf::loadView('certificates.event', [
            'event' => $event,
            'user' => $user,
            'feedback' => $feedback,
            'date' => now()->format('F j, Y')
        ]);

        Storage::disk('public')->put($certificatePath, $pdf->output());

        $feedback->update([
            'certificate_generated' => true,
            'certificate_path' => $certificatePath
        ]);
    }
}