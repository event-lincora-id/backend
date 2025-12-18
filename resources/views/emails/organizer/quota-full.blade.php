@extends('emails.layouts.master')

@section('title', 'Quota Full - ' . $event->title)

@section('content')
{{-- Badge --}}
@component('emails.components.badge', [
    'bgColor' => '#fef3c7',
    'textColor' => '#d97706'
])
    Quota Full
@endcomponent

{{-- Title and Greeting --}}
<table width="100%" cellpadding="0" cellspacing="0" border="0">
  <tr>
    <td align="center" style="padding-bottom: 24px;">
      <h1 class="h1-mobile" style="margin: 0; font-size: 24px; color: #1e293b; font-weight: 700;">Event at Full Capacity</h1>
      <p style="margin: 12px 0 0 0; color: #64748b; font-size: 16px;">
        Hello {{ $organizer->name }}, your event has reached maximum capacity!
      </p>
    </td>
  </tr>
</table>

{{-- Main Message --}}
<table width="100%" cellpadding="0" cellspacing="0" border="0">
  <tr>
    <td style="padding-bottom: 32px;">
      <p style="margin: 0; color: #475569; font-size: 16px; line-height: 1.6;">
        Congratulations! Your event has reached its maximum capacity. No more registrations will be accepted unless you increase the quota or spots become available.
      </p>
    </td>
  </tr>
</table>

{{-- Event Details --}}
@component('emails.components.detail-table', ['title' => $event->title])
    <tr>
      <td class="label">Event Date</td>
      <td class="value">{{ $event->start_date->format('l, d F Y') }}</td>
    </tr>
    <tr>
      <td class="label">Event Time</td>
      <td class="value">{{ $event->start_date->format('H:i') }} - {{ $event->end_date->format('H:i') }}</td>
    </tr>
    @if($event->location)
    <tr>
      <td class="label">Location</td>
      <td class="value">{{ $event->location }}</td>
    </tr>
    @endif
@endcomponent

{{-- Statistics --}}
@component('emails.components.detail-table', ['title' => 'Registration Statistics'])
    <tr>
      <td class="label">Total Registered</td>
      <td class="value" style="font-weight: 700; font-size: 18px; color: #d97706;">{{ $event->registered_count }} / {{ $event->quota }}</td>
    </tr>
    <tr>
      <td class="label">Capacity</td>
      <td class="value" style="font-weight: 700; color: #d97706;">100%</td>
    </tr>
    <tr>
      <td class="label">Registration Status</td>
      <td class="value" style="color: #dc2626; font-weight: 600;">Closed</td>
    </tr>
    @if($event->is_paid && isset($totalRevenue))
    <tr>
      <td class="label">Total Revenue</td>
      <td class="value" style="font-weight: 700; color: #15803d;">IDR {{ number_format($totalRevenue, 0, ',', '.') }}</td>
    </tr>
    @endif
@endcomponent

{{-- Suggestions --}}
@component('emails.components.info-box', [
    'title' => 'What You Can Do',
    'bgColor' => '#fffbeb',
    'borderColor' => '#fbbf24'
])
    <ul style="margin: 0; padding-left: 20px; color: #475569; font-size: 14px;">
      <li style="margin-bottom: 4px;">Consider increasing the event quota if you can accommodate more participants.</li>
      <li style="margin-bottom: 4px;">Review the participant list and manage registrations.</li>
      <li>Spots will become available if participants cancel their registration.</li>
    </ul>
@endcomponent

{{-- CTA Button --}}
@component('emails.components.button', ['url' => config('app.frontend_url') . '/organizer/events/' . $event->id])
    Manage Event
@endcomponent
@endsection
