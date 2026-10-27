<?php
http_response_code(403);
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 - Akses Ditolak</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f4f6f9;
        }
        .denied-card {
            max-width: 560px;
            width: 100%;
        }
        .denied-code {
            font-size: 4rem;
            font-weight: 700;
            line-height: 1;
            color: #1C476C;
        }
    </style>
</head>
<body>
    <main class="denied-card p-3">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4 p-md-5 text-center">
                <div class="denied-code">403</div>
                <h1 class="h4 mt-3 mb-2">Akses Ditolak</h1>
                <p class="text-muted mb-4">
                    Anda tidak memiliki hak akses untuk membuka halaman ini.
                </p>
                <a href="/" class="btn btn-primary">Kembali ke Halaman Utama</a>
            </div>
        </div>
    </main>
</body>
</html>
