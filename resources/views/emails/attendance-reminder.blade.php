@extends('emails.layouts.master')

@section('title', 'Attendance Reminder - ' . $event->title)

@section('content')
{{-- Badge --}}
@component('emails.components.badge', [
    'bgColor' => '#fee2e2',
    'textColor' => '#dc2626'
])
    Attendance Required
@endcomponent

{{-- Join Online Meeting Button or Pending Notice - PROMINENT PLACEMENT --}}
@if(in_array($event->event_type, ['online', 'hybrid']))
  @if($event->meeting_link)
    @component('emails.components.join-meeting-button', ['meetingLink' => $event->meeting_link, 'margin' => '32px 0'])
    @endcomponent
  @else
    @component('emails.components.meeting-link-pending', ['eventId' => $event->id, 'margin' => '32px 0'])
    @endcomponent
  @endif
@endif

{{-- Title and Greeting --}}
<table width="100%" cellpadding="0" cellspacing="0" border="0">
  <tr>
    <td align="center" style="padding-bottom: 24px;">
      <h1 class="h1-mobile" style="margin: 0; font-size: 24px; color: #1e293b; font-weight: 700;">Don't Forget to Check In!</h1>
      <p style="margin: 12px 0 0 0; color: #64748b; font-size: 16px;">
        Hello {{ $user->name }}, please confirm your attendance.
      </p>
    </td>
  </tr>
</table>

{{-- Main Message --}}
<table width="100%" cellpadding="0" cellspacing="0" border="0">
  <tr>
    <td style="padding-bottom: 32px;">
      <p style="margin: 0 0 16px 0; color: #475569; font-size: 16px; line-height: 1.6;">
        The event is {{ $isFinalReminder ? 'ending soon' : 'currently running' }}! Don't forget to check in using your QR code to confirm your attendance.
      </p>
      <p style="margin: 0; color: #475569; font-size: 16px; line-height: 1.6;">
        {{ $isFinalReminder ? 'This is your final reminder - the event ends in 5 minutes.' : 'Check in now to ensure your attendance is recorded.' }}
      </p>
    </td>
  </tr>
</table>

{{-- Event Details --}}
@component('emails.components.detail-table', ['title' => $event->title])
    <tr>
      <td class="label">Current Time</td>
      <td class="value">{{ now()->format('l, d F Y') }} at {{ now()->format('H:i') }}</td>
    </tr>
    <tr>
      <td class="label">Event Ends</td>
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
@endcomponent

{{-- QR Code Reminder --}}
@component('emails.components.info-box', [
    'title' => 'Your QR Code',
    'bgColor' => '#f0f9ff',
    'borderColor' => '#0284c7'
])
    <p style="margin: 0 0 8px 0; font-size: 14px; line-height: 1.6;">
      Show your QR code to the event organizer to check in. You can access it from your event dashboard.
    </p>
    <p style="margin: 0; font-size: 14px; line-height: 1.6; font-weight: 600;">
      QR Code: {{ $participant->qr_code_string ?? 'Available in dashboard' }}
    </p>
@endcomponent

{{-- Important Info --}}
@component('emails.components.info-box', [
    'title' => $isFinalReminder ? 'Final Reminder' : 'Reminder',
    'bgColor' => $isFinalReminder ? '#fef2f2' : '#fffbeb',
    'borderColor' => $isFinalReminder ? '#ef4444' : '#fbbf24'
])
    <p style="margin: 0; font-size: 14px; line-height: 1.6;">
      {{ $isFinalReminder ? 'Please check in immediately! The event is ending soon and your attendance will not be recorded if you don\'t check in.' : 'Checking in helps us track attendance and ensures you receive event updates and certificates (if applicable).' }}
    </p>
@endcomponent

{{-- CTA Button --}}
@component('emails.components.button', ['url' => config('app.frontend_url') . '/events/' . $event->id, 'color' => $isFinalReminder ? '#dc2626' : '#4f46e5'])
    {{ $isFinalReminder ? 'Check In Now' : 'View QR Code' }}
@endcomponent
@endsection
