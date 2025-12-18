@extends('emails.layouts.master')

@section('title', 'Registration Confirmed - ' . $event->title)

@section('content')
{{-- Badge --}}
@component('emails.components.badge', [
    'bgColor' => '#dcfce7',
    'textColor' => '#15803d'
])
    Registration Confirmed
@endcomponent

{{-- Title and Greeting --}}
<table width="100%" cellpadding="0" cellspacing="0" border="0">
  <tr>
    <td align="center" style="padding-bottom: 24px;">
      <h1 class="h1-mobile" style="margin: 0; font-size: 24px; color: #1e293b; font-weight: 700;">You're Registered!</h1>
      <p style="margin: 12px 0 0 0; color: #64748b; font-size: 16px;">
        Hello {{ $user->name }}, your registration is confirmed.
      </p>
    </td>
  </tr>
</table>

{{-- Main Message --}}
<table width="100%" cellpadding="0" cellspacing="0" border="0">
  <tr>
    <td style="padding-bottom: 32px;">
      <p style="margin: 0; color: #475569; font-size: 16px; line-height: 1.6;">
        You have successfully registered for the following event. We look forward to seeing you there!
      </p>
    </td>
  </tr>
</table>

{{-- Event Details --}}
@component('emails.components.detail-table', ['title' => $event->title])
    <tr>
      <td class="label">Start Time</td>
      <td class="value">{{ $event->start_date->format('l, d F Y') }} at {{ $event->start_date->format('H:i') }}</td>
    </tr>
    <tr>
      <td class="label">End Time</td>
      <td class="value">{{ $event->end_date->format('l, d F Y') }} at {{ $event->end_date->format('H:i') }}</td>
    </tr>

    @if($event->location)
    <tr>
      <td class="label">Location</td>
      <td class="value">{{ $event->location }}</td>
    </tr>
    @endif

    @if($event->event_type)
    <tr>
      <td class="label">Type</td>
      <td class="value">{{ ucfirst($event->event_type) }}</td>
    </tr>
    @endif

    @if($event->contact_info)
    <tr>
      <td class="label">Contact</td>
      <td class="value">{{ $event->contact_info }}</td>
    </tr>
    @endif
@endcomponent

{{-- QR Code Info --}}
@component('emails.components.info-box', [
    'title' => 'Attendance QR Code',
    'bgColor' => '#f0f9ff',
    'borderColor' => '#0284c7'
])
    <p style="margin: 0 0 8px 0; font-size: 14px; line-height: 1.6;">
      Your unique QR code is ready for attendance check-in. You can view and download it from your event dashboard.
    </p>
    <p style="margin: 0; font-size: 14px; line-height: 1.6; font-weight: 600;">
      QR Code: {{ $participant->qr_code_string ?? 'Available in dashboard' }}
    </p>
@endcomponent

{{-- Important Reminders --}}
@component('emails.components.info-box', [
    'title' => 'Important Reminders',
    'bgColor' => '#fffbeb',
    'borderColor' => '#fbbf24'
])
    <ul style="margin: 0; padding-left: 20px; color: #475569; font-size: 14px;">
      <li style="margin-bottom: 4px;">Please arrive 15 minutes early for check-in.</li>
      <li style="margin-bottom: 4px;">Bring your registration confirmation.</li>
      <li>Don't forget to check in using your QR code.</li>
    </ul>
@endcomponent

{{-- CTA Button --}}
@component('emails.components.button', ['url' => config('app.frontend_url') . '/events/' . $event->id])
    View Event Details
@endcomponent
@endsection
