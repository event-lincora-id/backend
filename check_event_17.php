<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Mail;

$testEmail = 'lkings013@gmail.com'; // Change to your email

$event = \App\Models\Event::first();
$user = \App\Models\User::first();
$participant = \App\Models\EventParticipant::first();

if (!$event || !$user || !$participant) {
    die("Please create test data first\n");
}

$organizer = $event->organizer;

echo "Testing all emails...\n\n";

// 1. Event Reminder H-1
echo "1. Sending Event Reminder H-1...\n";
Mail::to($testEmail)->send(new \App\Mail\EventReminderMail($event, $user, 'h1'));

// 2. Event Reminder H-0
echo "2. Sending Event Reminder H-0...\n";
Mail::to($testEmail)->send(new \App\Mail\EventReminderMail($event, $user, 'h0'));

// 3. Registration Confirmation
echo "3. Sending Registration Confirmation...\n";
Mail::to($testEmail)->send(new \App\Mail\RegistrationConfirmationMail($event, $user, $participant));

// 4. Invoice Email
echo "4. Sending Invoice Email...\n";
Mail::to($testEmail)->send(new \App\Mail\InvoiceCreatedMail($event, $user, $participant, 'https://checkout.xendit.co/test'));

// 5. Payment Success
echo "5. Sending Payment Success...\n";
Mail::to($testEmail)->send(new \App\Mail\PaymentSuccessMail($event, $user, $participant));

// 6. Attendance Reminder
echo "6. Sending Attendance Reminder...\n";
Mail::to($testEmail)->send(new \App\Mail\AttendanceReminderMail($event, $user, $participant, false));

// 7. Password Reset
echo "7. Sending Password Reset...\n";
$user->notify(new \App\Notifications\ResetPasswordNotification(\Illuminate\Support\Str::random(60)));

// 8. Participant Join (Organizer)
echo "8. Sending Participant Join Notification...\n";
Mail::to($testEmail)->send(new \App\Mail\Organizer\ParticipantJoinMail($event, $organizer, $participant, 500000));

// 9. Quota Full (Organizer)
echo "9. Sending Quota Full Alert...\n";
Mail::to($testEmail)->send(new \App\Mail\Organizer\QuotaFullMail($event, $organizer, 1000000));

// 10. Event Summary (Organizer)
echo "10. Sending Event Summary...\n";
$stats = [
    'total_registered' => 50,
    'total_attended' => 45,
    'attendance_rate' => 90.0,
    'total_feedback' => 30,
    'average_rating' => 4.5,
    'total_revenue' => 2500000,
];
Mail::to($testEmail)->send(new \App\Mail\Organizer\EventSummaryMail($event, $organizer, $stats, "Overall positive feedback!"));

echo "\nAll emails sent! Check your inbox or logs.\n";
