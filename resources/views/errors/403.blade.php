<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Ditolak - Lembaga Bahasa UM Metro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #dc2626 0%, #ea580c 50%, #f59e0b 100%);
            padding: 20px;
        }
        
        .container {
            text-align: center;
            max-width: 520px;
            background: white;
            padding: 50px 40px;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        
        .error-code {
            font-size: 120px;
            font-weight: 800;
            background: linear-gradient(135deg, #dc2626 0%, #ea580c 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 10px;
        }
        
        .icon-container {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #dc2626 0%, #ea580c 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            animation: shake 1s ease-in-out infinite;
        }
        
        .icon-container i {
            font-size: 36px;
            color: white;
        }
        
        @keyframes shake {
            0%, 100% {
                transform: rotate(0deg);
            }
            25% {
                transform: rotate(-5deg);
            }
            75% {
                transform: rotate(5deg);
            }
        }
        
        h1 {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 12px;
        }
        
        p {
            font-size: 15px;
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 28px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #dc2626 0%, #ea580c 100%);
            color: white;
            padding: 14px 28px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.3);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 38, 38, 0.4);
        }
        
        .btn i {
            font-size: 16px;
        }
        
        .suggestions {
            margin-top: 30px;
            padding-top: 24px;
            border-top: 1px solid #e2e8f0;
        }
        
        .suggestions p {
            font-size: 13px;
            color: #94a3b8;
            margin-bottom: 12px;
        }
        
        .suggestions a {
            color: #ea580c;
            text-decoration: none;
            font-weight: 500;
            margin: 0 8px;
            transition: color 0.2s;
        }
        
        .suggestions a:hover {
            color: #dc2626;
            text-decoration: underline;
        }
        
        .logo-section {
            margin-top: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .logo-section img {
            height: 52px;
            width: 52px;
            object-fit: contain;
        }
        
        .logo-text {
            text-align: left;
        }
        
        .logo-text .brand {
            font-weight: 800;
            font-size: 22px;
            color: #1e293b;
        }
        
        .logo-text .brand span {
            color: #f59e0b;
        }
        
        .logo-text .sub {
            font-size: 11px;
            color: #64748b;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon-container">
            <i class="fa-solid fa-shield-halved"></i>
        </div>
        <div class="error-code">403</div>
        <h1>Akses Ditolak</h1>
        <p>
            Maaf, Anda tidak memiliki izin untuk mengakses halaman ini. 
            Silakan hubungi administrator jika Anda merasa ini adalah kesalahan.
        </p>
        <a href="{{ url('/') }}" class="btn">
            <i class="fa-solid fa-home"></i>
            Kembali ke Beranda
        </a>
        
        <div class="suggestions">
            <p>Atau kunjungi halaman berikut:</p>
            <a href="{{ url('/') }}">Beranda</a>
            <a href="{{ route('login') }}">Login</a>
        </div>
        
        <div class="logo-section">
            <img src="{{ asset('images/logo-um.png') }}" alt="Logo UM Metro">
            <div class="logo-text">
                <div class="brand">Lembaga<span>Bahasa</span></div>
                <div class="sub">Universitas Muhammadiyah Metro</div>
            </div>
        </div>
    </div>
</body>
</html>