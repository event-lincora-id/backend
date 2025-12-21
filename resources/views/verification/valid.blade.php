<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Sertifikat Valid</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 40px;
            max-width: 600px;
            text-align: center;
        }
        .status-icon {
            font-size: 64px;
            color: #4CAF50;
            margin-bottom: 20px;
        }
        h1 {
            color: #2d3748;
            margin-bottom: 10px;
        }
        .info-box {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: bold;
            color: #4a5568;
        }
        .info-value {
            color: #2d3748;
        }
        .btn {
            display: inline-block;
            background: #D32F2F;
            color: white;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            margin-top: 20px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #b71c1c;
        }
        .verification-code {
            font-family: monospace;
            background: #edf2f7;
            padding: 8px 12px;
            border-radius: 4px;
            display: inline-block;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="status-icon">✓</div>
        <h1>Sertifikat Valid</h1>
        <p>Sertifikat ini telah diverifikasi dan sah.</p>

        <div class="verification-code">
            {{ $verificationCode }}
        </div>

        <div class="info-box">
            <div class="info-row">
                <span class="info-label">Nama Peserta:</span>
                <span class="info-value">{{ $user->full_name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Nama Acara:</span>
                <span class="info-value">{{ $event->title }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Penyelenggara:</span>
                <span class="info-value">{{ $event->organizer->full_name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Tanggal Acara:</span>
                <span class="info-value">{{ $event->start_date->format('d F Y') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Rating:</span>
                <span class="info-value">{{ $feedback->rating }}/5 ⭐</span>
            </div>
        </div>

        <a href="{{ route('certificate.download', $verificationCode) }}" class="btn">
            📄 Download Sertifikat
        </a>
    </div>
</body>
</html>
