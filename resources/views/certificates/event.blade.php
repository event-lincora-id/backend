<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Sertifikat {{ $event->title }}</title>
    <style>
        @page { size: landscape; margin: 0; }
        body {
            font-family: 'Montserrat', 'Segoe UI', Tahoma, sans-serif;
            margin: 0;
            padding: 0;
            color: #1a202c;
        }
        
        .cert-wrapper {
            width: 250mm; /* Ukuran A4 Landscape */
            height: 170mm;
            padding: 15mm;
            box-sizing: border-box;
            background: #fff;
            position: relative;
            margin: auto;
            border: 15px solid #D32F2F;  
            overflow: hidden; 
        } 

        .header {
            width: 100%;
            margin-bottom: 20px;
        }
        .platform-name {
            float: left;
            font-size: 18px;
            font-weight: 800;
            color: #D32F2F;
            letter-spacing: 1px;
        }
        .eo-logo {
            float: right;
        }
        .eo-logo img {
            max-height: 50px;
            max-width: 150px;
        }
        .clearfix {
            clear: both;
        }

        .main-content {
            text-align: center;
            position: relative;
            z-index: 2;
        }
        .cert-title {
            font-size: 46px;
            font-weight: 900;
            margin: 5px 0;
            color: #2d3748;
            letter-spacing: 2px;
        }
        .cert-subtitle {
            font-size: 16px;
            color: #718096;
            margin-top: 3px;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 3px;
        }

        .awarded-to {
            margin: 15px 0 8px 0;
            font-style: italic;
            font-size: 18px;
            color: #4a5568;
        }
        .participant-name {
            font-size: 38px;
            font-weight: 800;
            color: #D32F2F;
            border-bottom: 2px solid #e2e8f0;
            display: inline-block;
            padding: 0 40px 5px 40px;
            margin-bottom: 15px;
        }

        .event-statement {
            font-size: 16px;
            max-width: 700px;
            margin: 8px auto;
            line-height: 1.5;
        }

        .event-box {
            background: #f8fafc;
            padding: 12px 20px;
            margin: 15px auto;
            display: inline-block;
            border-radius: 8px;
            border: 1px solid #edf2f7;
        }

        .footer-sig {
            position: absolute;
            bottom: 20mm;
            left: -20mm;
            width: 100%;
            text-align: center;
        }
        .sig-item {
            display: inline-block;
            vertical-align: bottom;
            text-align: center;
            width: 40%;
        }
        .sig-img {
            max-height: 70px;
            margin-bottom: 5px;
        }
        .sig-line {
            border-top: 1px solid #2d3748;
            padding-top: 5px;
            font-weight: bold;
            font-size: 14px;
        }
        .cert-verification {
            position: absolute;
            bottom: 8mm;
            left: 15mm;
            display: inline-block;
        }
        .cert-verification img {
            display: block;
            margin-bottom: 10mm;
        }
        .cert-id {
            font-size: 9px;
            color: #a0aec0;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="cert-wrapper">
    <div class="header">
        <div class="platform-name">EVENT CONNECT</div>
        <div class="eo-logo">
            @if($event->organizer->logo)
                <img src="{{ public_path('storage/' . $event->organizer->logo) }}" alt="EO Logo">
            @endif
        </div>
        <div class="clearfix"></div>
    </div>

    <div class="main-content">
        <h1 class="cert-title">SERTIFIKAT</h1>
        <div class="cert-subtitle">PENGHARGAAN & PARTISIPASI</div>

        <p class="awarded-to">Diberikan secara terhormat kepada:</p>
        <div class="participant-name">{{ strtoupper($user->full_name) }}</div>

        <p class="event-statement">
            Atas partisipasi aktif dan keberhasilannya dalam mengikuti kegiatan:
        </p>

        <div class="event-box">
            <strong style="font-size: 22px; color: #1a202c;">{{ $event->title }}</strong><br>
            <span style="color: #718096;">Diselenggarakan pada {{ $event->start_date->format('d F Y') }}</span>
        </div>
    </div>

    <div class="footer-sig">
        <div class="sig-item"> 
                @if(isset($qrCodePath) && file_exists($qrCodePath))
                    <img src="{{ $qrCodePath }}" alt="Verification QR Code" style="width: 90px; height: 90px;">
                @endif
                <div class="cert-id">
                    ID: {{ $verificationCode ?? (Str::upper(Str::random(10)) . '-' . $event->id) }}<br>
                    <span style="font-size: 8px;">Scan QR untuk verifikasi</span> <br>
                </div>
                <span style="font-size: 12px;">Tanggal Terbit: {{ date('d/m/Y') }}</span> 
        </div>

        <div class="sig-item">
            @if($event->organizer->signature)
                <img src="{{ public_path('storage/' . $event->organizer->signature) }}" class="sig-img" alt="TTD">
            @else
                <div style="height: 60px;"></div>
            @endif
            <div class="sig-line">
                {{ $event->organizer->full_name }}<br>
                <span style="font-weight: normal; font-size: 11px;">Penyelenggara Acara</span>
            </div>
        </div>
    </div>
</div>

</body>
</html>
