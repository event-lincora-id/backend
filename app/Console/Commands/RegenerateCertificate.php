<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Feedback;
use App\Models\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class RegenerateCertificate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'certificate:regenerate {event_id : The event ID to regenerate certificates for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate certificates for a specific event with updated template';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $eventId = $this->argument('event_id');

        // Get event
        $event = Event::find($eventId);
        if (!$event) {
            $this->error("Event with ID {$eventId} not found!");
            return 1;
        }

        // Get all feedbacks for this event
        $feedbacks = Feedback::where('event_id', $eventId)
            ->where('certificate_generated', true)
            ->get();

        if ($feedbacks->isEmpty()) {
            $this->info("No certificates found for event: {$event->title}");
            return 0;
        }

        $this->info("Found {$feedbacks->count()} certificate(s) for event: {$event->title}");

        if (!$this->confirm('Do you want to regenerate these certificates?', true)) {
            $this->info('Cancelled.');
            return 0;
        }

        $bar = $this->output->createProgressBar($feedbacks->count());
        $bar->start();

        foreach ($feedbacks as $feedback) {
            // Delete old certificate
            if ($feedback->certificate_path) {
                Storage::disk('public')->delete($feedback->certificate_path);
            }

            $user = $feedback->user;

            // Generate verification code if not exists
            if (!$feedback->verification_code) {
                $verificationCode = 'CERT-' . strtoupper(Str::random(10)) . '-' . $event->id;
                $feedback->verification_code = $verificationCode;
            } else {
                $verificationCode = $feedback->verification_code;
            }

            // Generate QR code (SVG format to avoid imagick dependency)
            $qrCodePath = 'qr_codes/certificates/' . $verificationCode . '.svg';
            $qrCodeFullPath = storage_path('app/public/' . $qrCodePath);
            $qrCodeDir = dirname($qrCodeFullPath);
            if (!file_exists($qrCodeDir)) {
                mkdir($qrCodeDir, 0755, true);
            }

            $verificationUrl = config('app.url') . '/verify/' . $verificationCode;
            QrCode::format('svg')
                ->size(200)
                ->margin(1)
                ->errorCorrection('M')
                ->generate($verificationUrl, $qrCodeFullPath);

            // Generate new certificate
            $filename = 'certificate_' . $event->id . '_' . $user->id . '_' . time() . '.pdf';
            $certificatePath = 'certificates/' . $filename;

            $pdf = Pdf::loadView('certificates.event', [
                'event' => $event,
                'user' => $user,
                'feedback' => $feedback,
                'date' => now()->format('F j, Y'),
                'qrCodePath' => $qrCodeFullPath,
                'verificationCode' => $verificationCode
            ]);

            Storage::disk('public')->put($certificatePath, $pdf->output());

            $feedback->update([
                'certificate_generated' => true,
                'certificate_path' => $certificatePath,
                'verification_code' => $verificationCode
            ]);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Successfully regenerated {$feedbacks->count()} certificate(s)!");

        return 0;
    }
}
