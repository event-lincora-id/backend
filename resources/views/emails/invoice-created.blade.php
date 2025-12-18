@extends('emails.layouts.master')

@section('title', 'Payment Required - ' . $event->title)

@section('content')
{{-- Badge --}}
@component('emails.components.badge', [
    'bgColor' => '#fef3c7',
    'textColor' => '#d97706'
])
    Payment Required
@endcomponent

{{-- Title and Greeting --}}
<table width="100%" cellpadding="0" cellspacing="0" border="0">
  <tr>
    <td align="center" style="padding-bottom: 24px;">
      <h1 class="h1-mobile" style="margin: 0; font-size: 24px; color: #1e293b; font-weight: 700;">Complete Your Registration</h1>
      <p style="margin: 12px 0 0 0; color: #64748b; font-size: 16px;">
        Hello {{ $user->name }}, please complete your payment to secure your spot.
      </p>
    </td>
  </tr>
</table>

{{-- Main Message --}}
<table width="100%" cellpadding="0" cellspacing="0" border="0">
  <tr>
    <td style="padding-bottom: 32px;">
      <p style="margin: 0; color: #475569; font-size: 16px; line-height: 1.6;">
        You're one step away from joining this event! Complete your payment within 24 hours to confirm your registration.
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

{{-- Payment Details --}}
@component('emails.components.detail-table', ['title' => 'Payment Information'])
    <tr>
      <td class="label">Amount</td>
      <td class="value" style="font-size: 20px; font-weight: 700; color: #4f46e5;">IDR {{ number_format($event->price, 0, ',', '.') }}</td>
    </tr>
    <tr>
      <td class="label">Payment Due</td>
      <td class="value">24 hours from now</td>
    </tr>
    <tr>
      <td class="label">Payment Methods</td>
      <td class="value">Credit Card, Bank Transfer, E-Wallet, Virtual Account</td>
    </tr>
    <tr>
      <td class="label">Invoice ID</td>
      <td class="value">{{ $participant->payment_reference ?? 'N/A' }}</td>
    </tr>
@endcomponent

{{-- Important Info --}}
@component('emails.components.info-box', [
    'title' => 'Important',
    'bgColor' => '#fef2f2',
    'borderColor' => '#ef4444'
])
    <p style="margin: 0; font-size: 14px; line-height: 1.6;">
      This payment link will expire in 24 hours. After expiration, you'll need to register again.
    </p>
@endcomponent

{{-- CTA Button --}}
@component('emails.components.button', ['url' => $invoiceUrl, 'color' => '#15803d'])
    Pay Now
@endcomponent

{{-- Alternative Link --}}
<table width="100%" cellpadding="0" cellspacing="0" border="0">
  <tr>
    <td style="padding-top: 24px; border-top: 1px solid #e2e8f0;">
      <p style="margin: 0; color: #94a3b8; font-size: 12px; line-height: 1.6;">
        If the button doesn't work, copy this link:
      </p>
      <p style="margin: 8px 0 0 0; word-break: break-all;">
        <a href="{{ $invoiceUrl }}" style="color: #4f46e5; text-decoration: underline; font-size: 12px;">{{ $invoiceUrl }}</a>
      </p>
    </td>
  </tr>
</table>
@endsection
