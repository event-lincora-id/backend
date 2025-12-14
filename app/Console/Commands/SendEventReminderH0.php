<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Notification;
use App\Mail\EventReminderMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class SendEventReminderH0 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:send-reminders-h0';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send email reminders for events starting in 1 hour (H-0)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to send H-0 event reminders (1 hour before)...');

        // Get events starting in the next hour (current time + 60 minutes)
        // Using a 10-minute window (55-65 minutes) to account for cron timing
        $now = Carbon::now('Asia/Jakarta');
        $oneHourLater = $now->copy()->addHour();

        $startWindow = $oneHourLater->copy()->subMinutes(5);
        $endWindow = $oneHourLater->copy()->addMinutes(5);

        $events = Event::where('start_date', '>=', $startWindow)
            ->where('start_date', '<=', $endWindow)
            ->where('status', 'published')
            ->where('is_active', true)
            ->get();

        if ($events->isEmpty()) {
            $this->info('No events scheduled to start in 1 hour.');
            return 0;
        }

        $this->info("Found {$events->count()} event(s) starting in approximately 1 hour.");

        $totalSent = 0;
        $totalFailed = 0;

        foreach ($events as $event) {
            $this->info("Processing event: {$event->title}");

            // Get all confirmed participants for this event
            $participants = EventParticipant::where('event_id', $event->id)
                ->whereIn('status', ['registered'])
                ->with('user')
                ->get();

            if ($participants->isEmpty()) {
                $this->warn("  - No participants found for this event");
                continue;
            }

            foreach ($participants as $participant) {
                try {
                    // Check if reminder already sent in the last 2 hours (prevent duplicates)
                    $existingNotification = Notification::where('user_id', $participant->user_id)
                        ->where('event_id', $event->id)
                        ->where('type', 'event_reminder_h0')
                        ->where('created_at', '>=', Carbon::now()->subHours(2))
                        ->first();

                    if ($existingNotification) {
                        $this->warn("  - H-0 reminder already sent to {$participant->user->email}");
                        continue;
                    }

                    // Send email with H-0 reminder type
                    Mail::to($participant->user->email)
                        ->send(new EventReminderMail($event, $participant->user, 'h0'));

                    // Create notification record
                    Notification::create([
                        'user_id' => $participant->user_id,
                        'event_id' => $event->id,
                        'type' => 'event_reminder_h0',
                        'title' => 'Event dimulai 1 jam lagi!',
                        'message' => "Reminder: {$event->title} akan dimulai dalam 1 jam pada {$event->start_date->format('H:i')}. Jangan lupa untuk hadir!",
                        'is_read' => false,
                        'data' => [
                            'event_title' => $event->title,
                            'start_date' => $event->start_date->toDateTimeString(),
                            'end_date' => $event->end_date->toDateTimeString(),
                            'location' => $event->location,
                            'event_type' => $event->event_type,
                            'contact_info' => $event->contact_info,
                        ],
                    ]);

                    $totalSent++;
                    $this->info("  ✓ H-0 reminder sent to {$participant->user->email}");

                } catch (\Exception $e) {
                    $totalFailed++;
                    $this->error("  ✗ Failed to send H-0 reminder to {$participant->user->email}: {$e->getMessage()}");
                }
            }
        }

        $this->info("\n=== Summary ===");
        $this->info("Total H-0 emails sent: {$totalSent}");

        if ($totalFailed > 0) {
            $this->error("Total failed: {$totalFailed}");
        }

        $this->info('H-0 event reminders completed!');

        return 0;
    }
}
