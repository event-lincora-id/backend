<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;

class CertificateVerificationController extends Controller
{
    /**
     * Show certificate verification page
     */
    public function verify($verificationCode)
    {
        // Find feedback by verification code
        $feedback = Feedback::where('verification_code', $verificationCode)
            ->with(['user', 'event.organizer'])
            ->first();

        if (!$feedback || !$feedback->certificate_generated) {
            return view('verification.invalid', [
                'code' => $verificationCode
            ]);
        }

        // Check if certificate file exists
        $certificatePath = storage_path('app/public/' . $feedback->certificate_path);
        if (!file_exists($certificatePath)) {
            return view('verification.invalid', [
                'code' => $verificationCode,
                'reason' => 'Certificate file not found'
            ]);
        }

        return view('verification.valid', [
            'feedback' => $feedback,
            'user' => $feedback->user,
            'event' => $feedback->event,
            'verificationCode' => $verificationCode,
            'certificatePath' => asset('storage/' . $feedback->certificate_path)
        ]);
    }

    /**
     * Download verified certificate
     */
    public function download($verificationCode)
    {
        $feedback = Feedback::where('verification_code', $verificationCode)->first();

        if (!$feedback || !$feedback->certificate_generated) {
            abort(404, 'Certificate not found');
        }

        $certificatePath = storage_path('app/public/' . $feedback->certificate_path);
        if (!file_exists($certificatePath)) {
            abort(404, 'Certificate file not found');
        }

        return response()->download($certificatePath, 'certificate.pdf');
    }
}
