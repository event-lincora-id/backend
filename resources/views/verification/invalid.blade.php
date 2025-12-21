<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Sertifikat Gagal</title>
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
            color: #f44336;
            margin-bottom: 20px;
        }
        h1 {
            color: #2d3748;
            margin-bottom: 10px;
        }
        .error-message {
            color: #718096;
            margin: 20px 0;
        }
        .verification-code {
            font-family: monospace;
            background: #fee;
            padding: 8px 12px;
            border-radius: 4px;
            display: inline-block;
            margin: 10px 0;
            color: #c53030;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="status-icon">✗</div>
        <h1>Sertifikat Tidak Valid</h1>
        <p class="error-message">
            Kode verifikasi tidak ditemukan atau sertifikat tidak sah.
            @if(isset($reason))
                <br><strong>{{ $reason }}</strong>
            @endif
        </p>
        <div class="verification-code">
            {{ $code }}
        </div>
    </div>
</body>
</html>
