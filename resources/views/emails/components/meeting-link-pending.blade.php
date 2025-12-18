{{-- Meeting Link Pending Component - Shows when online/hybrid event doesn't have meeting link yet --}}
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: {{ $margin ?? '24px 0' }};">
  <tr>
    <td align="center" style="padding: 16px; background-color: #fef3c7; border-radius: 8px; border-left: 4px solid #f59e0b;">
      <table cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td align="center" style="padding-bottom: 12px;">
            <strong style="color: #d97706; font-size: 16px; display: block;">
              Online Meeting Information
            </strong>
          </td>
        </tr>
        <tr>
          <td align="center" style="padding-bottom: 8px;">
            <p style="margin: 0; font-size: 14px; color: #92400e; line-height: 1.5;">
              The online meeting link will be shared by the event organizer closer to the event date.
            </p>
          </td>
        </tr>
        <tr>
          <td align="center">
            <a href="{{ config('app.frontend_url') . '/events/' . $eventId }}"
               target="_blank"
               style="display: inline-block; padding: 12px 24px; background-color: #f59e0b; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px; margin-top: 8px;">
              Check Event Dashboard
            </a>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
