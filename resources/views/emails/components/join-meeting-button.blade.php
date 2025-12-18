{{-- Join Online Meeting Button Component --}}
@if(isset($meetingLink) && $meetingLink)
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: {{ $margin ?? '24px 0' }};">
  <tr>
    <td align="center" style="padding: 16px; background-color: #f0f9ff; border-radius: 8px; border-left: 4px solid #3b82f6;">
      <table cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td align="center" style="padding-bottom: 12px;">
            <strong style="color: #1e40af; font-size: 16px; display: block;">
              Join Online Event
            </strong>
          </td>
        </tr>
        <tr>
          <td align="center" style="padding-bottom: 8px;">
            <a href="{{ $meetingLink }}"
               class="btn meeting-btn"
               target="_blank"
               style="display: inline-block; padding: 14px 32px; background-color: #3b82f6; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px;">
              Join Meeting Now
            </a>
          </td>
        </tr>
        <tr>
          <td align="center">
            <p style="margin: 0; font-size: 12px; color: #64748b;">
              or copy link:
              <a href="{{ $meetingLink }}" target="_blank" style="color: #3b82f6; word-break: break-all;">
                {{ Str::limit($meetingLink, 50) }}
              </a>
            </p>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
@endif
