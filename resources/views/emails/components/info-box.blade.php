{{-- Info Box Component --}}
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: {{ $margin ?? '24px 0' }};">
  <tr>
    <td>
      <div class="info-box" style="background-color: {{ $bgColor ?? '#f8fafc' }}; border-left: 4px solid {{ $borderColor ?? '#4f46e5' }}; padding: 16px; border-radius: 4px;">
        @if(isset($title))
        <strong style="color: #1e293b; display: block; margin-bottom: 8px;">{{ $title }}</strong>
        @endif
        <div style="color: #475569; font-size: 14px; line-height: 1.6;">
          {{ $slot }}
        </div>
      </div>
    </td>
  </tr>
</table>
