{{-- Badge Component --}}
<table width="100%" cellpadding="0" cellspacing="0" border="0">
  <tr>
    <td align="center" style="padding-bottom: {{ $paddingBottom ?? '24px' }};">
      <span class="badge" style="display: inline-block; background-color: {{ $bgColor ?? '#fef3c7' }}; color: {{ $textColor ?? '#d97706' }}; padding: 6px 12px; border-radius: 9999px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">
        {{ $slot }}
      </span>
    </td>
  </tr>
</table>
