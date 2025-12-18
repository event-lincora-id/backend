{{-- Button Component --}}
<table width="100%" cellpadding="0" cellspacing="0" border="0">
  <tr>
    <td align="{{ $align ?? 'center' }}" style="padding-top: {{ $paddingTop ?? '16px' }}; padding-bottom: {{ $paddingBottom ?? '16px' }};">
      <a href="{{ $url }}" class="btn" style="display: inline-block; padding: 14px 32px; background-color: {{ $color ?? '#4f46e5' }}; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px;">
        {{ $slot }}
      </a>
    </td>
  </tr>
</table>
