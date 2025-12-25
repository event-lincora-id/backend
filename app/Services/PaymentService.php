<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\EventParticipant;
use App\Models\Event;
use App\Mail\PaymentSuccessMail;
use App\Mail\Organizer\ParticipantJoinMail;
use Xendit\Configuration;
use Xendit\Invoice\InvoiceApi;
use Xendit\Invoice\CreateInvoiceRequest;

class PaymentService
{
    protected $secretKey;
    protected $publicKey;
    protected $webhookToken;
    protected $callbackUrl;
    protected $redirectUrl;

    public function __construct()
    {
        $this->secretKey = config('services.xendit.secret_key');
        $this->publicKey = config('services.xendit.public_key');
        $this->webhookToken = config('services.xendit.webhook_token');
        $this->callbackUrl = config('services.xendit.callback_url');
        $this->redirectUrl = config('services.xendit.redirect_url');
        
        // Configure Xendit SDK
        Configuration::setXenditKey($this->secretKey);
    }

    /**
     * Create invoice for event payment
     */
    public function createInvoice(EventParticipant $participant, array $options = [])
    {
        try {
            $event = $participant->event;
            $user = $participant->user;

            // Create Xendit invoice using SDK
            $invoiceApi = new InvoiceApi();
            
            $createInvoiceRequest = new CreateInvoiceRequest([
                'external_id' => 'event_' . $event->id . '_participant_' . $participant->id . '_' . time(),
                'amount' => (float) $event->price,
                'description' => 'Payment for event: ' . $event->title,
                'invoice_duration' => 86400, // 24 hours
                'customer' => [
                    'given_names' => $user->full_name ?? $user->name,
                    'email' => $user->email,
                ],
                'customer_notification_preference' => [
                    'invoice_created' => [],
                    'invoice_reminder' => [],
                    'invoice_paid' => [],
                ],
                'success_redirect_url' => $this->redirectUrl . '?participant_id=' . $participant->id . '&status=success',
                'failure_redirect_url' => $this->redirectUrl . '?participant_id=' . $participant->id . '&status=failed',
                'currency' => 'IDR',
                'items' => [
                    [
                        'name' => $event->title,
                        'quantity' => 1,
                        'price' => (float) $event->price,
                        'category' => 'Event Registration',
                    ]
                ],
            ]);

            $invoice = $invoiceApi->createInvoice($createInvoiceRequest);

            // Update participant with payment reference
            $participant->update([
                'payment_reference' => $invoice['id'],
                'payment_url' => $invoice['invoice_url'],
                'payment_status' => 'pending',
            ]);

            Log::info('Xendit Invoice Created', [
                'participant_id' => $participant->id,
                'invoice_id' => $invoice['id'],
                'amount' => $event->price,
                'invoice_url' => $invoice['invoice_url'],
            ]);

            return [
                'success' => true,
                'invoice' => [
                    'id' => $invoice['id'],
                    'invoice_url' => $invoice['invoice_url'],
                    'amount' => $event->price,
                ],
                'payment_url' => $invoice['invoice_url'],
                'invoice_id' => $invoice['id'],
            ];

        } catch (\Exception $e) {
            Log::error('Xendit Invoice Creation Failed', [
                'participant_id' => $participant->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create virtual account for event payment
     */
    public function createVirtualAccount(EventParticipant $participant, string $bankCode = 'BCA')
    {
        try {
            $event = $participant->event;
            $user = $participant->user;

            // For now, simulate VA creation
            $vaId = 'va_' . time() . '_' . $participant->id;
            $accountNumber = '1234567890' . rand(1000, 9999);

            // Update participant with VA details
            $participant->update([
                'payment_reference' => $vaId,
                'payment_url' => $accountNumber,
                'payment_status' => 'pending',
                'payment_method' => 'virtual_account',
            ]);

            Log::info('Xendit Virtual Account Created (Simulated)', [
                'participant_id' => $participant->id,
                'va_id' => $vaId,
                'account_number' => $accountNumber,
            ]);

            return [
                'success' => true,
                'virtual_account' => [
                    'id' => $vaId,
                    'account_number' => $accountNumber,
                    'bank_code' => $bankCode,
                ],
                'account_number' => $accountNumber,
                'va_id' => $vaId,
            ];

        } catch (\Exception $e) {
            Log::error('Xendit Virtual Account Creation Failed', [
                'participant_id' => $participant->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create e-wallet payment
     */
    public function createEWalletPayment(EventParticipant $participant, string $ewalletType = 'OVO')
    {
        try {
            $event = $participant->event;
            $user = $participant->user;

            // For now, simulate e-wallet creation
            $ewalletId = 'ewallet_' . time() . '_' . $participant->id;
            $checkoutUrl = 'https://checkout.xendit.co/ewallet/' . $ewalletId;

            // Update participant with e-wallet details
            $participant->update([
                'payment_reference' => $ewalletId,
                'payment_url' => $checkoutUrl,
                'payment_status' => 'pending',
                'payment_method' => 'ewallet',
            ]);

            Log::info('Xendit E-Wallet Payment Created (Simulated)', [
                'participant_id' => $participant->id,
                'ewallet_id' => $ewalletId,
                'ewallet_type' => $ewalletType,
            ]);

            return [
                'success' => true,
                'ewallet' => [
                    'id' => $ewalletId,
                    'checkout_url' => $checkoutUrl,
                    'ewallet_type' => $ewalletType,
                ],
                'checkout_url' => $checkoutUrl,
                'ewallet_id' => $ewalletId,
            ];

        } catch (\Exception $e) {
            Log::error('Xendit E-Wallet Payment Creation Failed', [
                'participant_id' => $participant->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature($payload, $signature)
    {
        $expectedSignature = hash_hmac('sha256', $payload, $this->webhookToken);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Handle payment webhook
     */
    public function handleWebhook(array $webhookData)
    {
        try {
            // Check if this is a test webhook (missing external_id)
            if (!isset($webhookData['external_id']) || empty($webhookData['external_id'])) {
                Log::info('Test webhook received (no external_id) - ignoring', [
                    'webhook_data' => $webhookData
                ]);
                return true; // Return true so Xendit test passes
            }

            $externalId = $webhookData['external_id'];
            $status = $webhookData['status'];
            $paymentMethod = $webhookData['payment_method'] ?? 'unknown';

            // Extract participant ID from external_id
            // Support both old and new formats
            if (strpos($externalId, 'event_') === 0 && strpos($externalId, '_participant_') !== false) {
                // New format: event_{eventId}_participant_{participantId}_{timestamp}
                $parts = explode('_participant_', $externalId);
                if (count($parts) >= 2) {
                    $participantId = (int) explode('_', $parts[1])[0];
                } else {
                    Log::warning('Could not parse participant ID from external_id', ['external_id' => $externalId]);
                    return false;
                }
            } elseif (strpos($externalId, 'event_payment_') === 0) {
                // Old format: event_payment_{participantId}
                $participantId = explode('_', $externalId)[2];
            } elseif (strpos($externalId, 'event_va_') === 0) {
                // Old format: event_va_{participantId}
                $participantId = explode('_', $externalId)[2];
            } elseif (strpos($externalId, 'event_ewallet_') === 0) {
                // Old format: event_ewallet_{participantId}
                $participantId = explode('_', $externalId)[2];
            } else {
                Log::info('Test/Unknown external_id format in webhook - ignoring', [
                    'external_id' => $externalId,
                    'status' => $status
                ]);
                return true; // Return true for test webhooks
            }

            Log::info('Extracted participant ID from webhook', [
                'external_id' => $externalId,
                'participant_id' => $participantId
            ]);

            $participant = EventParticipant::find($participantId);
            if (!$participant) {
                Log::warning('Participant not found for webhook', ['participant_id' => $participantId]);
                return false;
            }

            // Update participant status based on payment status
            switch ($status) {
                case 'PAID':
                    // Update participant to REGISTERED status with payment confirmation
                    $wasRegistered = $participant->status === 'registered';
                    $participant->update([
                        'status' => 'registered',  // NOW user is registered
                        'payment_status' => 'paid',
                        'is_paid' => true,
                        'amount_paid' => $webhookData['amount'] ?? $participant->event->price,
                        'paid_at' => now(),
                    ]);

                    // NOW increment registered count (only for newly registered participants)
                    if (!$wasRegistered) {
                        $participant->event->increment('registered_count');
                    }

                    // Create notification for organizer
                    \App\Models\Notification::create([
                        'user_id' => $participant->event->user_id,
                        'event_id' => $participant->event_id,
                        'type' => 'event_registration',
                        'title' => 'New Paid Event Registration',
                        'message' => $participant->user->full_name . ' has registered and paid for: ' . $participant->event->title,
                        'data' => [
                            'participant_id' => $participant->id,
                            'participant_name' => $participant->user->full_name,
                            'amount_paid' => $participant->amount_paid
                        ]
                    ]);

                    // Send payment success email to participant
                    Log::info('🔍 Attempting to send payment success email', [
                        'participant_id' => $participant->id,
                        'participant_email' => $participant->user->email ?? 'NULL',
                        'event_id' => $participant->event_id,
                        'event_title' => $participant->event->title ?? 'N/A',
                        'payment_status' => $participant->payment_status,
                    ]);

                    try {
                        Mail::to($participant->user->email)->send(
                            new PaymentSuccessMail($participant->event, $participant->user, $participant)
                        );

                        Log::info('✅ Payment success email sent successfully', [
                            'participant_id' => $participant->id,
                            'email' => $participant->user->email,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('❌ Failed to send payment success email', [
                            'participant_id' => $participant->id,
                            'email' => $participant->user->email ?? 'NULL',
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }

                    // Send organizer notification email (only for newly registered)
                    if (!$wasRegistered) {
                        try {
                            // Calculate total revenue for this event
                            $totalRevenue = EventParticipant::where('event_id', $participant->event_id)
                                ->where('is_paid', true)
                                ->sum('amount_paid');

                            Mail::to($participant->event->organizer->email)->send(
                                new ParticipantJoinMail($participant->event, $participant->event->organizer, $participant, $totalRevenue)
                            );
                        } catch (\Exception $e) {
                            Log::error('Failed to send organizer notification email', [
                                'participant_id' => $participant->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }

                    // Add balance to organizer (NEW)
                    try {
                        app(\App\Services\BalanceService::class)->addPaymentToBalance($participant);
                    } catch (\Exception $e) {
                        Log::error('Failed to add payment to organizer balance', [
                            'participant_id' => $participant->id,
                            'error' => $e->getMessage()
                        ]);
                    }

                    // Check if event is now full and send quota full alert to organizer
                    $participant->event->refresh(); // Reload to get updated registered_count
                    if ($participant->event->quota && $participant->event->registered_count >= $participant->event->quota && !$participant->event->quota_full_notified) {
                        try {
                            $totalRevenue = EventParticipant::where('event_id', $participant->event_id)
                                ->where('is_paid', true)
                                ->sum('amount_paid');

                            Mail::to($participant->event->organizer->email)->send(
                                new \App\Mail\Organizer\QuotaFullMail($participant->event, $participant->event->organizer, $totalRevenue)
                            );

                            // Mark as notified
                            $participant->event->update(['quota_full_notified' => true]);

                            Log::info('Quota full notification sent via webhook', [
                                'event_id' => $participant->event_id,
                                'registered_count' => $participant->event->registered_count,
                                'quota' => $participant->event->quota,
                            ]);
                        } catch (\Exception $e) {
                            Log::error('Failed to send quota full alert via webhook', [
                                'event_id' => $participant->event_id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }

                    Log::info('Participant registered after payment', [
                        'participant_id' => $participant->id,
                        'event_id' => $participant->event_id,
                        'amount_paid' => $participant->amount_paid
                    ]);
                    break;

                case 'EXPIRED':
                    $participant->update([
                        'payment_status' => 'expired',
                        'status' => 'cancelled',  // Cancel registration for expired payment
                    ]);

                    Log::info('Payment expired, registration cancelled', [
                        'participant_id' => $participant->id
                    ]);
                    break;

                case 'FAILED':
                    $participant->update([
                        'payment_status' => 'failed',
                        'status' => 'cancelled',  // Cancel registration for failed payment
                    ]);

                    Log::info('Payment failed, registration cancelled', [
                        'participant_id' => $participant->id
                    ]);
                    break;

                default:
                    Log::info('Unknown payment status in webhook', [
                        'status' => $status,
                        'participant_id' => $participantId,
                    ]);
            }

            Log::info('Payment webhook processed', [
                'participant_id' => $participantId,
                'status' => $status,
                'payment_method' => $paymentMethod,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'webhook_data' => $webhookData,
            ]);

            return false;
        }
    }

    /**
     * Get payment status from Xendit
     */
    public function getPaymentStatus(string $paymentReference)
    {
        try {
            // Call Xendit API to get invoice status
            $invoiceApi = new InvoiceApi();
            $invoice = $invoiceApi->getInvoiceById($paymentReference);

            Log::info('Retrieved invoice status from Xendit', [
                'invoice_id' => $paymentReference,
                'status' => $invoice['status'] ?? 'unknown',
                'paid_amount' => $invoice['paid_amount'] ?? 0,
            ]);

            return [
                'success' => true,
                'status' => $invoice['status'] ?? 'PENDING',
                'amount' => $invoice['amount'] ?? 0,
                'paid_amount' => $invoice['paid_amount'] ?? 0,
                'invoice_url' => $invoice['invoice_url'] ?? null,
                'expiry_date' => $invoice['expiry_date'] ?? null,
                'payment_method' => $invoice['payment_method'] ?? null,
            ];

        } catch (\Xendit\XenditSdkException $e) {
            Log::error('Xendit SDK error while getting payment status', [
                'payment_reference' => $paymentReference,
                'error' => $e->getMessage(),
                'full_error' => $e->getFullError(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get payment status', [
                'payment_reference' => $paymentReference,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get available payment methods
     */
    public function getAvailablePaymentMethods()
    {
        return [
            'invoice' => [
                'name' => 'Credit Card',
                'description' => 'Pay with Visa, Mastercard, or JCB',
                'icon' => 'fas fa-credit-card',
            ],
            'virtual_account' => [
                'name' => 'Virtual Account',
                'description' => 'Pay via BCA, BNI, BRI, or Mandiri',
                'icon' => 'fas fa-university',
                'banks' => ['BCA', 'BNI', 'BRI', 'MANDIRI'],
            ],
            'ewallet' => [
                'name' => 'E-Wallet',
                'description' => 'Pay with OVO, DANA, LinkAja, or ShopeePay',
                'icon' => 'fas fa-mobile-alt',
                'providers' => ['OVO', 'DANA', 'LINKAJA', 'SHOPEEPAY'],
            ],
        ];
    }
}