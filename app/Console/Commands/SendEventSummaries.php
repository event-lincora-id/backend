<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Mail\Organizer\EventSummaryMail;
use App\Services\AIService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SendEventSummaries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:send-summaries';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send event summary emails with AI-generated feedback analysis';

    protected AIService $aiService;

    public function __construct(AIService $aiService)
    {
        parent::__construct();
        $this->aiService = $aiService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for events ready for summary...');

        $now = Carbon::now('Asia/Jakarta');
        $oneHourAgo = $now->copy()->subHour();

        // Find events that:
        // 1. Ended at least 1 hour ago OR
        // 2. Have feedback >= 50% of registered participants
        // 3. Summary not sent yet
        $events = Event::where('status', 'published')
            ->where('is_active', true)
            ->whereNull('summary_sent_at')
            ->where(function ($query) use ($oneHourAgo) {
                // Events that ended 1+ hours ago
                $query->where('end_date', '<=', $oneHourAgo);

                // OR events with sufficient feedback (will be checked below)
            })
            ->get();

        $totalSent = 0;
        $totalFailed = 0;

        foreach ($events as $event) {
            $this->info("Processing event: {$event->title}");

            // Calculate statistics
            $statistics = $this->calculateStatistics($event);

            // Check feedback condition
            $feedbackRate = $statistics['feedback_rate'];
            $hasEnded = $event->end_date <= $oneHourAgo;

            if (!$hasEnded && $feedbackRate < 50) {
                $this->warn("  - Skipping: Event hasn't ended and feedback rate ({$feedbackRate}%) < 50%");
                continue;
            }

            // Generate AI feedback summary if feedback exists
            $feedbackSummary = null;
            if ($statistics['total_feedback'] > 0) {
                $this->info("  - Generating AI feedback summary...");
                $feedbackSummary = $this->generateFeedbackSummary($event);

                if (str_starts_with($feedbackSummary, 'API ERROR') || str_starts_with($feedbackSummary, 'EXCEPTION')) {
                    $this->warn("  - AI Summary failed: {$feedbackSummary}");
                    $feedbackSummary = null;
                }
            }

            try {
                // Send email to organizer
                Mail::to($event->organizer->email)
                    ->send(new EventSummaryMail($event, $event->organizer, $statistics, $feedbackSummary));

                // Mark as sent
                $event->summary_sent_at = $now;
                $event->save();

                $totalSent++;
                $this->info("  ✓ Summary sent to {$event->organizer->email}");

            } catch (\Exception $e) {
                $totalFailed++;
                $this->error("  ✗ Failed to send summary: {$e->getMessage()}");
            }
        }

        $this->info("\n=== Summary ===");
        $this->info("Events processed: {$events->count()}");
        $this->info("Total summaries sent: {$totalSent}");

        if ($totalFailed > 0) {
            $this->error("Total failed: {$totalFailed}");
        }

        $this->info('Event summaries completed!');

        return 0;
    }

    /**
     * Calculate event statistics
     */
    private function calculateStatistics(Event $event): array
    {
        $totalRegistered = EventParticipant::where('event_id', $event->id)
            ->whereIn('status', ['registered', 'attended'])
            ->count();

        $totalAttended = EventParticipant::where('event_id', $event->id)
            ->where('status', 'attended')
            ->count();

        $attendanceRate = $totalRegistered > 0 ? ($totalAttended / $totalRegistered) * 100 : 0;

        // Get feedback data
        $feedbackData = DB::table('feedbacks')
            ->where('event_id', $event->id)
            ->select(DB::raw('COUNT(*) as total'), DB::raw('AVG(rating) as avg_rating'))
            ->first();

        $totalFeedback = $feedbackData->total ?? 0;
        $averageRating = $feedbackData->avg_rating ?? 0;
        $feedbackRate = $totalAttended > 0 ? ($totalFeedback / $totalAttended) * 100 : 0;

        $totalRevenue = null;
        if ($event->is_paid) {
            $totalRevenue = EventParticipant::where('event_id', $event->id)
                ->where('is_paid', true)
                ->sum('amount_paid');
        }

        return [
            'total_registered' => $totalRegistered,
            'total_attended' => $totalAttended,
            'attendance_rate' => $attendanceRate,
            'total_feedback' => $totalFeedback,
            'average_rating' => $averageRating,
            'feedback_rate' => $feedbackRate,
            'total_revenue' => $totalRevenue,
        ];
    }

    /**
     * Generate AI-powered feedback summary
     */
    private function generateFeedbackSummary(Event $event): ?string
    {
        // Get all feedback for the event
        $feedbacks = DB::table('feedbacks')
            ->where('event_id', $event->id)
            ->select('rating', 'comment')
            ->get()
            ->map(function ($feedback) {
                return [
                    'rating' => $feedback->rating,
                    'comment' => $feedback->comment ?? 'No comment provided',
                ];
            })
            ->toArray();

        if (empty($feedbacks)) {
            return null;
        }

        // Use existing AIService to generate summary
        return $this->aiService->generateFeedbackSummary($feedbacks);
    }
}
