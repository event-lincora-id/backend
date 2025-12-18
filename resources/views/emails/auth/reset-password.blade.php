@extends('emails.layouts.master')

@section('title', 'Reset Password - ' . config('app.name'))

@section('content')
{{-- Badge --}}
@component('emails.components.badge', [
    'bgColor' => '#dbeafe',
    'textColor' => '#1e40af'
])
    Password Reset Request
@endcomponent

{{-- Title and Greeting --}}
<table width="100%" cellpadding="0" cellspacing="0" border="0">
  <tr>
    <td align="center" style="padding-bottom: 24px;">
      <h1 class="h1-mobile" style="margin: 0; font-size: 24px; color: #1e293b; font-weight: 700;">Reset Your Password</h1>
      <p style="margin: 12px 0 0 0; color: #64748b; font-size: 16px;">
        Hello!
      </p>
    </td>
  </tr>
</table>

{{-- Main Message --}}
<table width="100%" cellpadding="0" cellspacing="0" border="0">
  <tr>
    <td style="padding-bottom: 32px;">
      <p style="margin: 0 0 16px 0; color: #475569; font-size: 16px; line-height: 1.6;">
        You are receiving this email because we received a password reset request for your account.
      </p>
      <p style="margin: 0; color: #475569; font-size: 16px; line-height: 1.6;">
        Click the button below to reset your password. This link will expire in 60 minutes.
      </p>
    </td>
  </tr>
</table>

{{-- CTA Button --}}
@component('emails.components.button', ['url' => $resetUrl])
    Reset Password
@endcomponent

{{-- Security Info --}}
@component('emails.components.info-box', [
    'title' => 'Security Notice',
    'bgColor' => '#fef2f2',
    'borderColor' => '#ef4444'
])
    <p style="margin: 0; font-size: 14px; line-height: 1.6;">
      If you did not request a password reset, no further action is required. Your password will remain unchanged.
    </p>
@endcomponent

{{-- Alternative Link --}}
<table width="100%" cellpadding="0" cellspacing="0" border="0">
  <tr>
    <td style="padding-top: 24px; border-top: 1px solid #e2e8f0;">
      <p style="margin: 0; color: #94a3b8; font-size: 12px; line-height: 1.6;">
        If you're having trouble clicking the button, copy and paste the URL below into your web browser:
      </p>
      <p style="margin: 8px 0 0 0; word-break: break-all;">
        <a href="{{ $resetUrl }}" style="color: #4f46e5; text-decoration: underline; font-size: 12px;">{{ $resetUrl }}</a>
      </p>
    </td>
  </tr>
</table>
@endsection
