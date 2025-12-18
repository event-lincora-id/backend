{{-- Detail Table Component --}}
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f8fafc; border-radius: 8px; padding: 24px; border: 1px solid #f1f5f9; margin: {{ $margin ?? '20px 0' }};">
  @if(isset($title))
  <tr>
    <td colspan="2" style="padding-bottom: 16px; border-bottom: 2px solid #e2e8f0;">
      <h2 style="margin: 0; font-size: 18px; color: #334155;">{{ $title }}</h2>
    </td>
  </tr>
  @endif
  <tr>
    <td>
      <table class="detail-table" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top: {{ isset($title) ? '16px' : '0' }};">
        {{ $slot }}
      </table>
    </td>
  </tr>
</table>
