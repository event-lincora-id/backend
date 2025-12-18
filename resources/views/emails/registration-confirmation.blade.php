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

    @if($event->event_type)
    <tr>
      <td class="label">Event Type</td>
      <td class="value">
        <span style="display: inline-block; padding: 4px 12px; background-color: {{ $event->event_type === 'online' ? '#dbeafe' : ($event->event_type === 'hybrid' ? '#fef3c7' : '#f3f4f6') }}; color: {{ $event->event_type === 'online' ? '#1e40af' : ($event->event_type === 'hybrid' ? '#d97706' : '#374151') }}; border-radius: 12px; font-size: 12px; font-weight: 600;">
          {{ ucfirst($event->event_type) }}
        </span>
      </td>
    </tr>
    @endif

    @if($event->location && in_array($event->event_type, ['offline', 'hybrid']))
    <tr>
      <td class="label">Location</td>
      <td class="value">{{ $event->location }}</td>
    </tr>
    @endif

    @if($event->meeting_link && in_array($event->event_type, ['online', 'hybrid']))
    <tr>
      <td class="label">Meeting Link</td>
      <td class="value">
        <a href="{{ $event->meeting_link }}" target="_blank" style="color: #3b82f6; word-break: break-all;">
          {{ Str::limit($event->meeting_link, 60) }}
        </a>
      </td>
    </tr>
    @endif

    @if($event->contact_info)
    <tr>
      <td class="label">Contact</td>
      <td class="value">{{ $event->contact_info }}</td>
    </tr>
    @endif
@endcomponent

{{-- Join Online Meeting Button or Pending Notice --}}
@if(in_array($event->event_type, ['online', 'hybrid']))
  @if($event->meeting_link)
    @component('emails.components.join-meeting-button', ['meetingLink' => $event->meeting_link])
    @endcomponent
  @else
    @component('emails.components.meeting-link-pending', ['eventId' => $event->id])
    @endcomponent
  @endif
@endif

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
