<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <title>@yield('title', config('app.name'))</title>
  <style type="text/css">
    /* Client-specific Resets */
    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    img { -ms-interpolation-mode: bicubic; max-width: 100%; height: auto; }

    /* General Styles */
    body {
      background-color: #f1f5f9;
      font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
      margin: 0;
      padding: 0;
      width: 100% !important;
      line-height: 1.6;
      color: #334155;
    }
    .wrapper { width: 100%; table-layout: fixed; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; background-color: #f1f5f9; }
    .webkit { max-width: 600px; margin: 0 auto; }

    /* Utility Classes */
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .text-muted { color: #64748b; }
    .text-sm { font-size: 14px; }
    .font-bold { font-weight: bold; }

    /* Components */
    .btn {
      display: inline-block;
      padding: 14px 32px;
      background-color: #4f46e5; /* Indigo 600 */
      color: #ffffff;
      text-decoration: none;
      border-radius: 6px;
      font-weight: 600;
      font-size: 16px;
      mso-padding-alt: 0;
      text-align: center;
      transition: background-color 0.2s ease;
    }
    .btn:hover { background-color: #4338ca; }

    .card {
      background-color: #ffffff;
      border-radius: 8px;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
      overflow: hidden;
      border: 1px solid #e2e8f0;
    }

    .header-bar {
      background-color: #1e293b; /* Slate 800 */
      padding: 30px 40px;
      text-align: center;
    }

    .badge {
      display: inline-block;
      background-color: #fef3c7;
      color: #d97706;
      padding: 6px 12px;
      border-radius: 9999px;
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    .detail-table td {
      padding: 12px 0;
      border-bottom: 1px solid #f1f5f9;
    }
    .detail-table tr:last-child td {
      border-bottom: none;
    }
    .label {
      color: #64748b;
      font-size: 14px;
      width: 140px;
      vertical-align: top;
    }
    .value {
      color: #0f172a;
      font-weight: 500;
    }

    .info-box {
      background-color: #f8fafc;
      border-left: 4px solid #4f46e5;
      padding: 16px;
      border-radius: 4px;
      margin: 24px 0;
    }

    /* Mobile Responsiveness */
    @media screen and (max-width: 600px) {
      .webkit {
        width: 100% !important;
        max-width: 100% !important;
      }
      .card {
        border-radius: 0 !important;
        border: none !important;
        box-shadow: none !important;
      }
      .header-bar {
        padding: 24px 20px !important;
      }
      .body-content {
        padding: 24px 20px !important;
      }
      .btn {
        display: block !important;
        width: 100% !important;
        box-sizing: border-box !important;
        padding: 16px 20px !important;
      }
      .h1-mobile {
        font-size: 22px !important;
      }

      /* Stack detail table for better readability on small screens */
      .detail-table, .detail-table tbody, .detail-table tr, .detail-table td {
        display: block !important;
        width: 100% !important;
        box-sizing: border-box !important;
      }
      .detail-table td.label {
        padding-bottom: 2px !important;
        width: 100% !important;
        font-size: 12px !important;
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }
      .detail-table td.value {
        padding-top: 0 !important;
        padding-bottom: 20px !important;
        border-bottom: 1px solid #f1f5f9 !important;
      }
      .detail-table tr:last-child td.value {
        border-bottom: none !important;
      }
    }
  </style>
</head>
<body>
  <div class="wrapper">
    <table class="webkit" align="center" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 auto;">
      <tr>
        <td style="padding: 20px 0;">

          <!-- Main Card -->
          <table class="card" width="100%" cellpadding="0" cellspacing="0" border="0">

            <!-- Header -->
            <tr>
              <td class="header-bar">
                <div style="font-size: 24px; font-weight: bold; color: #ffffff; letter-spacing: -0.5px;">
                  {{ config('app.name') }}
                </div>
              </td>
            </tr>

            <!-- Body Content -->
            <tr>
              <td class="body-content" style="padding: 40px;">
                @yield('content')
              </td>
            </tr>

            <!-- Footer -->
            <tr>
              <td style="background-color: #f8fafc; padding: 24px; text-align: center; border-top: 1px solid #e2e8f0;">
                <p style="margin: 0 0 12px 0; color: #94a3b8; font-size: 12px;">
                  You received this email because you are registered on {{ config('app.name') }}.
                </p>
                <p style="margin: 0; color: #94a3b8; font-size: 12px;">
                  &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved. <br/>
                  Questions? Contact <a href="mailto:{{ config('mail.from.address') }}" style="color: #64748b; text-decoration: underline;">{{ config('mail.from.address') }}</a>
                </p>
              </td>
            </tr>
          </table>

        </td>
      </tr>
    </table>
  </div>
</body>
</html>
