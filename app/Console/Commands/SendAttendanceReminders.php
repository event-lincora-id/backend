<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Mail\AttendanceReminderMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class SendAttendanceReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:send-attendance-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send attendance reminders for ongoing events and final reminders 5 minutes before end';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for events requiring attendance reminders...');

        $now = Carbon::now('Asia/Jakarta');
        $fiveMinutesLater = $now->copy()->addMinutes(5);

        // Find events that are currently running
        $ongoingEvents = Event::where('start_date', '<=', $now)
            ->where('end_date', '>', $now)
            ->where('status', 'published')
            ->where('is_active', true)
            ->get();

        $totalSent = 0;
        $totalFailed = 0;

        foreach ($ongoingEvents as $event) {
            $this->info("Processing event: {$event->title}");

            // Check if event is ending soon (within 5-10 minutes)
            $isEndingSoon = $event->end_date->between($now, $fiveMinutesLater->copy()->addMinutes(5));

            // Get participants who haven't attended yet
            $participants = EventParticipant::where('event_id', $event->id)
                ->where('status', 'registered') // Not attended yet
                ->whereIn('status', ['registered'])
                ->with('user')
                ->get();

            foreach ($participants as $participant) {
                try {
                    // Determine if this is a final reminder or regular reminder
                    $isFinalReminder = $isEndingSoon && !$participant->attendance_reminder_final_sent;
                    $isRegularReminder = !$isEndingSoon && !$participant->attendance_reminder_sent;

                    // Skip if already sent appropriate reminder
                    if (!$isRegularReminder && !$isFinalReminder) {
                        continue;
                    }

                    // Send email
                    Mail::to($participant->user->email)
                        ->send(new AttendanceReminderMail($event, $participant->user, $participant, $isFinalReminder));

                    // Update reminder sent flags
                    if ($isFinalReminder) {
                        $participant->attendance_reminder_final_sent = true;
                        $participant->save();
                        $this->info("  ✓ Final reminder sent to {$participant->user->email}");
                    } else {
                        $participant->attendance_reminder_sent = true;
                        $participant->save();
                        $this->info("  ✓ Regular reminder sent to {$participant->user->email}");
                    }

                    $totalSent++;

                } catch (\Exception $e) {
                    $totalFailed++;
                    $this->error("  ✗ Failed to send reminder to {$participant->user->email}: {$e->getMessage()}");
                }
            }
        }

        $this->info("\n=== Summary ===");
        $this->info("Events processed: {$ongoingEvents->count()}");
        $this->info("Total reminders sent: {$totalSent}");

        if ($totalFailed > 0) {
            $this->error("Total failed: {$totalFailed}");
        }

        $this->info('Attendance reminders completed!');

        return 0;
    }
}
