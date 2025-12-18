@extends('emails.layouts.master')

@section('title', 'Event Summary - ' . $event->title)

@section('content')
{{-- Badge --}}
@component('emails.components.badge', [
    'bgColor' => '#dcfce7',
    'textColor' => '#15803d'
])
    Event Completed
@endcomponent

{{-- Title and Greeting --}}
<table width="100%" cellpadding="0" cellspacing="0" border="0">
  <tr>
    <td align="center" style="padding-bottom: 24px;">
      <h1 class="h1-mobile" style="margin: 0; font-size: 24px; color: #1e293b; font-weight: 700;">Your Event Summary</h1>
      <p style="margin: 12px 0 0 0; color: #64748b; font-size: 16px;">
        Hello {{ $organizer->name }}, here's how your event performed.
      </p>
    </td>
  </tr>
</table>

{{-- Main Message --}}
<table width="100%" cellpadding="0" cellspacing="0" border="0">
  <tr>
    <td style="padding-bottom: 32px;">
      <p style="margin: 0; color: #475569; font-size: 16px; line-height: 1.6;">
        Your event has concluded. Below is a comprehensive summary of your event's performance, including participant statistics and feedback analysis.
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
      <td class="label">Duration</td>
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
@component('emails.components.detail-table', ['title' => 'Event Statistics'])
    <tr>
      <td class="label">Total Registered</td>
      <td class="value" style="font-weight: 700; font-size: 16px;">{{ $statistics['total_registered'] }}</td>
    </tr>
    <tr>
      <td class="label">Total Attended</td>
      <td class="value" style="font-weight: 700; font-size: 16px;">{{ $statistics['total_attended'] }}</td>
    </tr>
    <tr>
      <td class="label">Attendance Rate</td>
      <td class="value" style="font-weight: 700; color: {{ $statistics['attendance_rate'] >= 75 ? '#15803d' : ($statistics['attendance_rate'] >= 50 ? '#d97706' : '#dc2626') }};">{{ number_format($statistics['attendance_rate'], 1) }}%</td>
    </tr>
    @if(isset($statistics['total_feedback']) && $statistics['total_feedback'] > 0)
    <tr>
      <td class="label">Total Feedback</td>
      <td class="value" style="font-weight: 700;">{{ $statistics['total_feedback'] }}</td>
    </tr>
    <tr>
      <td class="label">Average Rating</td>
      <td class="value" style="font-weight: 700; font-size: 18px; color: #4f46e5;">{{ number_format($statistics['average_rating'], 1) }} / 5.0</td>
    </tr>
    <tr>
      <td class="label">Feedback Rate</td>
      <td class="value">{{ number_format($statistics['feedback_rate'], 1) }}%</td>
    </tr>
    @endif
    @if($event->is_paid && isset($statistics['total_revenue']))
    <tr>
      <td class="label">Total Revenue</td>
      <td class="value" style="font-weight: 700; font-size: 16px; color: #15803d;">IDR {{ number_format($statistics['total_revenue'], 0, ',', '.') }}</td>
    </tr>
    @endif
@endcomponent

{{-- AI Feedback Summary --}}
@if(isset($feedbackSummary) && $feedbackSummary)
@component('emails.components.info-box', [
    'title' => 'AI-Generated Feedback Summary',
    'bgColor' => '#f0f9ff',
    'borderColor' => '#0284c7'
])
    <div style="font-size: 14px; line-height: 1.8; color: #334155; white-space: pre-line;">{{ $feedbackSummary }}</div>
@endcomponent
@else
@component('emails.components.info-box', [
    'title' => 'Feedback Summary',
    'bgColor' => '#f1f5f9',
    'borderColor' => '#94a3b8'
])
    <p style="margin: 0; font-size: 14px; line-height: 1.6;">
        No feedback has been received yet for this event. Feedback summaries help you understand participant experience and improve future events.
    </p>
@endcomponent
@endif

{{-- Performance Indicators --}}
@component('emails.components.info-box', [
    'title' => 'Performance Highlights',
    'bgColor' => '#fffbeb',
    'borderColor' => '#fbbf24'
])
    <ul style="margin: 0; padding-left: 20px; color: #475569; font-size: 14px; line-height: 1.8;">
      <li>Attendance Rate: {{ number_format($statistics['attendance_rate'], 1) }}% ({{ $statistics['total_attended'] }} of {{ $statistics['total_registered'] }} participants)</li>
      @if(isset($statistics['total_feedback']) && $statistics['total_feedback'] > 0)
      <li>Average Rating: {{ number_format($statistics['average_rating'], 1) }}/5.0 stars</li>
      <li>Feedback Response: {{ number_format($statistics['feedback_rate'], 1) }}% of attendees</li>
      @endif
      @if($event->is_paid && isset($statistics['total_revenue']))
      <li>Revenue Generated: IDR {{ number_format($statistics['total_revenue'], 0, ',', '.') }}</li>
      @endif
    </ul>
@endcomponent

{{-- CTA Button --}}
@component('emails.components.button', ['url' => config('app.frontend_url') . '/organizer/events/' . $event->id . '/report'])
    View Full Report
@endcomponent

{{-- Thank You Message --}}
<table width="100%" cellpadding="0" cellspacing="0" border="0">
  <tr>
    <td style="padding-top: 32px; text-align: center;">
      <p style="margin: 0; color: #64748b; font-size: 15px; line-height: 1.6;">
        Thank you for using {{ config('app.name') }} to manage your event!
      </p>
    </td>
  </tr>
</table>
@endsection
