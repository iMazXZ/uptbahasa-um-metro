<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>503 | Layanan Tidak Tersedia</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 20px;
            background: #ffffff;
            color: #111827;
            font-family: "Segoe UI", Arial, Helvetica, sans-serif;
        }

        .wrap {
            width: 100%;
            max-width: 480px;
        }

        .content {
            text-align: center;
        }

        .code {
            font-size: 56px;
            font-weight: 700;
            line-height: 1;
            letter-spacing: -0.04em;
            margin-bottom: 16px;
        }

        h1 {
            font-size: 24px;
            font-weight: 600;
            line-height: 1.35;
            margin-bottom: 10px;
        }

        p {
            font-size: 15px;
            line-height: 1.7;
            color: #4b5563;
            margin-bottom: 24px;
        }

        .actions {
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 140px;
            padding: 11px 16px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            background: #ffffff;
            color: #111827;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
        }

        .btn:hover {
            background: #f9fafb;
        }

        @media (max-width: 640px) {
            .code {
                font-size: 48px;
            }

            h1 {
                font-size: 21px;
            }

            p {
                font-size: 14px;
            }

            .actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <main class="wrap">
        <section class="content" aria-labelledby="error-title">
            <div class="code" aria-hidden="true">503</div>
            <h1 id="error-title">Website sedang dalam proses pembaruan.</h1>
            <p>Silakan coba kembali beberapa saat lagi.</p>

            <div class="actions">
                <button type="button" class="btn" onclick="window.location.reload()">Coba Lagi</button>
                <a href="{{ url('/') }}" class="btn">Kembali ke Beranda</a>
            </div>
        </section>
    </main>
</body>
</html>
