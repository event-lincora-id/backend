<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Send event reminders daily at 9:00 AM for events happening tomorrow (H-1)
        $schedule->command('events:send-reminders')
            ->dailyAt('09:00')
            ->timezone('Asia/Jakarta')
            ->withoutOverlapping()
            ->onSuccess(function () {
                \Log::info('Event reminders sent successfully');
            })
            ->onFailure(function () {
                \Log::error('Event reminders failed to send');
            });

        // Send event reminders hourly for events starting in 1 hour (H-0)
        $schedule->command('events:send-reminders-h0')
            ->hourly()
            ->timezone('Asia/Jakarta')
            ->withoutOverlapping()
            ->onSuccess(function () {
                \Log::info('H-0 event reminders sent successfully');
            })
            ->onFailure(function () {
                \Log::error('H-0 event reminders failed to send');
            });

        // Optional: Send reminders multiple times a day
        // $schedule->command('events:send-reminders')
        //     ->twiceDaily(9, 18) // 9 AM and 6 PM
        //     ->timezone('Asia/Jakarta');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}