@extends('emails.layouts.master')

@section('title', 'New Registration - ' . $event->title)

@section('content')
{{-- Badge --}}
@component('emails.components.badge', [
    'bgColor' => '#dbeafe',
    'textColor' => '#1e40af'
])
    New Registration
@endcomponent

{{-- Title and Greeting --}}
<table width="100%" cellpadding="0" cellspacing="0" border="0">
  <tr>
    <td align="center" style="padding-bottom: 24px;">
      <h1 class="h1-mobile" style="margin: 0; font-size: 24px; color: #1e293b; font-weight: 700;">New Participant Registered</h1>
      <p style="margin: 12px 0 0 0; color: #64748b; font-size: 16px;">
        Hello {{ $organizer->name }}, someone just registered for your event!
      </p>
    </td>
  </tr>
</table>

{{-- Main Message --}}
<table width="100%" cellpadding="0" cellspacing="0" border="0">
  <tr>
    <td style="padding-bottom: 32px;">
      <p style="margin: 0; color: #475569; font-size: 16px; line-height: 1.6;">
        A new participant has successfully registered and completed payment for your event.
      </p>
    </td>
  </tr>
</table>

{{-- Participant Details --}}
@component('emails.components.detail-table', ['title' => 'Participant Information'])
    <tr>
      <td class="label">Name</td>
      <td class="value">{{ $participant->user->name ?? 'N/A' }}</td>
    </tr>
    <tr>
      <td class="label">Email</td>
      <td class="value">{{ $participant->user->email ?? 'N/A' }}</td>
    </tr>
    <tr>
      <td class="label">Registration Date</td>
      <td class="value">{{ $participant->created_at->format('d F Y, H:i') }}</td>
    </tr>
    @if($participant->amount_paid)
    <tr>
      <td class="label">Payment Amount</td>
      <td class="value" style="font-weight: 700; color: #15803d;">IDR {{ number_format($participant->amount_paid, 0, ',', '.') }}</td>
    </tr>
    @endif
@endcomponent

{{-- Event Statistics --}}
@component('emails.components.detail-table', ['title' => $event->title . ' - Statistics'])
    <tr>
      <td class="label">Total Registered</td>
      <td class="value" style="font-weight: 700;">{{ $event->registered_count }} {{ $event->quota ? '/ ' . $event->quota : '' }}</td>
    </tr>
    @if($event->quota)
    <tr>
      <td class="label">Remaining Spots</td>
      <td class="value">{{ max(0, $event->quota - $event->registered_count) }}</td>
    </tr>
    <tr>
      <td class="label">Capacity</td>
      <td class="value">{{ round(($event->registered_count / $event->quota) * 100, 1) }}%</td>
    </tr>
    @endif
    @if($event->is_paid && isset($totalRevenue))
    <tr>
      <td class="label">Total Revenue</td>
      <td class="value" style="font-weight: 700; color: #15803d;">IDR {{ number_format($totalRevenue, 0, ',', '.') }}</td>
    </tr>
    @endif
@endcomponent

{{-- CTA Button --}}
@component('emails.components.button', ['url' => config('app.frontend_url') . '/organizer/events/' . $event->id . '/participants'])
    View All Participants
@endcomponent
@endsection
