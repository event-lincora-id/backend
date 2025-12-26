@extends('emails.layouts.master')

@section('title', 'Event Update - ' . $event->title)

@section('content')
{{-- Badge --}}
@component('emails.components.badge', [
    'bgColor' => '#fef3c7',
    'textColor' => '#d97706'
])
    Event Updated
@endcomponent

{{-- Title and Greeting --}}
<table width="100%" cellpadding="0" cellspacing="0" border="0">
  <tr>
    <td align="center" style="padding-bottom: 24px;">
      <h1 class="h1-mobile" style="margin: 0; font-size: 24px; color: #1e293b; font-weight: 700;">Event Details Changed</h1>
      <p style="margin: 12px 0 0 0; color: #64748b; font-size: 16px;">
        Hello {{ $participantName }}, there are important updates to your event.
      </p>
    </td>
  </tr>
</table>

{{-- Main Message --}}
<table width="100%" cellpadding="0" cellspacing="0" border="0">
  <tr>
    <td style="padding-bottom: 32px;">
      <p style="margin: 0; color: #475569; font-size: 16px; line-height: 1.6;">
        The event organizer has made some changes to <strong>{{ $event->title }}</strong>. Please review the updates below.
      </p>
    </td>
  </tr>
</table>

{{-- Changes Summary --}}
@component('emails.components.info-box', [
    'title' => 'What Changed?',
    'bgColor' => '#fef2f2',
    'borderColor' => '#ef4444'
])
    @foreach($changes as $change)
        <div style="margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid #fee2e2;">
            <p style="margin: 0 0 8px 0; font-size: 14px; font-weight: 600; color: #991b1b;">
                {{ $change['label'] }}
            </p>

            @if(isset($change['old']))
            <div style="margin-bottom: 8px;">
                <span style="font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: 600;">Previous:</span>
                <p style="margin: 4px 0 0 0; font-size: 14px; color: #475569; text-decoration: line-through; opacity: 0.7;">
                    {{ $change['old'] }}
                </p>
            </div>
            @endif

            <div>
                <span style="font-size: 12px; color: #15803d; text-transform: uppercase; font-weight: 600;">New:</span>
                <p style="margin: 4px 0 0 0; font-size: 14px; color: #1e293b; font-weight: 600;">
                    @if($change['field'] === 'meeting_link' && filter_var($change['new'], FILTER_VALIDATE_URL))
                        <a href="{{ $change['new'] }}" target="_blank" style="color: #3b82f6; word-break: break-all;">
                            {{ Str::limit($change['new'], 60) }}
                        </a>
                    @else
                        {{ $change['new'] }}
                    @endif
                </p>
            </div>
        </div>
    @endforeach
@endcomponent

{{-- Current Event Details --}}
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

{{-- Join Online Meeting Button if applicable --}}
@if(in_array($event->event_type, ['online', 'hybrid']) && $event->meeting_link)
  @component('emails.components.join-meeting-button', ['meetingLink' => $event->meeting_link])
  @endcomponent
@endif

{{-- Important Notice --}}
@component('emails.components.info-box', [
    'title' => 'Important Notice',
    'bgColor' => '#fffbeb',
    'borderColor' => '#fbbf24'
])
    <p style="margin: 0; font-size: 14px; line-height: 1.6; color: #475569;">
      Please make note of these changes. If you have any questions or concerns, feel free to contact the event organizer.
    </p>
@endcomponent

{{-- CTA Button --}}
@component('emails.components.button', ['url' => config('app.frontend_url') . '/events/' . $event->id])
    View Event Details
@endcomponent
@endsection
