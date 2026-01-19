<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>7-Star Transformational Leadership Assessment</title>
    
    <link rel="icon" href="{{ asset('images/bintang.png') }}" type="image/png">
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* --- RESET & BASIC SETUP --- */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            width: 100%;
            min-height: 100vh;
            color: white;
            overflow-x: hidden;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        /* --- BACKGROUND LAYER --- */
        .bg-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }

        /* 1. Gambar Asli */
        .bg-image {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background-image: url("{{ asset('images/background-7star.png') }}");
        background-size: cover; 
        background-position: center center; 
        background-repeat: no-repeat; 
        z-index: -1; 
}

        2. Overlay Biru Gelap (KUNCI DESAIN AWAL)
        /* Ini membuat gambar jadi gelap & biru, mirip desain gradasi awal */
        .bg-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 50% 30%, rgba(15, 30, 69, 0.85) 0%, rgba(2, 6, 23, 0.95) 100%);
        }

        /* --- NAVBAR --- */
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 40px 80px;
            width: 100%;
            z-index: 10;
        }

        .logo-img {
            height: 80px;
            width: auto;
            object-fit: contain;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.3));
        }

        .nav-buttons {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .btn {
            padding: 10px 32px;
            /* UBAH DARI 50px JADI 12px (KOTAK) */
            border-radius: 12px; 
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: 0.3s;
            text-decoration: none;
            text-align: center;
        }

        .btn-login {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            backdrop-filter: blur(4px);
        }
        
        .btn-login:hover {
            border-color: white;
            background: rgba(255, 255, 255, 0.15);
        }

        .btn-register {
            background: linear-gradient(90deg, #d64732 0%, #b52b19 100%);
            border: none;
            color: white;
            box-shadow: 0 4px 15px rgba(181, 43, 25, 0.3);
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(181, 43, 25, 0.5);
            filter: brightness(1.1);
        }

        /* --- HERO SECTION --- */
        .hero {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center; 
            text-align: center;
            padding: 40px 20px;    
            z-index: 5; 
        }

        .sub-headline {
            font-size: 1.1rem;
            font-weight: 300;
            color: #cbd5e1;
            margin-bottom: 15px;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .main-headline {
            font-size: 3.5rem;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 50px; 
            text-shadow: 0 4px 20px rgba(0,0,0,0.5);
            background: linear-gradient(to bottom, #ffffff, #e2e8f0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* --- CTA BUTTON --- */
        .cta-button {
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.6);
            color: white;
            padding: 18px 45px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 90px;
            line-height: 1;
        }

        .cta-button:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: #fff;
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.15);
        }

        /* --- CARDS SECTION --- */
        .cards-container {
            display: flex;
            gap: 30px;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
        }

        .glass-card {
            width: 240px;
            height: 150px;
            /* Kaca yang lebih "Deep" sesuai desain awal */
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            backdrop-filter: blur(20px); 
            -webkit-backdrop-filter: blur(20px);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
            transition: transform 0.3s ease;
        }

        .glass-card:hover {
            transform: translateY(-10px);
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .card-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #60a5fa; /* Biru terang */
            line-height: 1;
            text-shadow: 0 0 20px rgba(96, 165, 250, 0.3);
        }

        .card-desc {
            font-size: 0.85rem;
            color: #e2e8f0;
            font-weight: 400;
            letter-spacing: 0.5px;
        }

        .star-icon-card {
            width: 50px;
            height: 50px;
            object-fit: contain;
            display: block;
        }

        /* --- FOOTER --- */
        footer {
            text-align: center;
            padding: 30px;
            font-size: 0.8rem;
            color: rgba(255,255,255,0.4);
            z-index: 10;
        }

        /* --- RESPONSIVE MOBILE --- */
        @media (max-width: 900px) {
            nav {
                flex-direction: column;
                gap: 20px;
                padding: 30px 20px;
            }
            .nav-buttons {
                width: 100%;
                justify-content: center;
            }
            .btn {
                padding: 12px 0;
                flex: 1; 
            }
            .main-headline { 
                font-size: 2rem; 
                margin-bottom: 40px;
            }
            .cta-button {
                width: 100%;
                max-width: 320px;
                text-align: center;
                margin-bottom: 50px;
            }
            .cards-container { 
                flex-direction: column; 
                width: 100%;
            }
            .glass-card { 
                width: 100%; 
                max-width: 320px; 
            }
        }
    </style>
</head>
<body>

    <div class="bg-container">
        <div class="bg-image"></div>
        <div class="bg-overlay"></div>
    </div>

    <nav>
        <div class="logo-container">
            <a href="/">
                <img src="{{ asset('images/logo-7star.png') }}" alt="7-Star Logo" class="logo-img">
            </a>
        </div>

        @if (Route::has('login'))
            <div class="nav-buttons">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-register">DASHBOARD</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-login">MASUK</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="btn btn-register">DAFTAR</a>
                    @endif
                @endauth
            </div>
        @endif
    </nav>

    <main class="hero">
        <h3 class="sub-headline">Transformational Leadership Assessment</h3>
        
        <h1 class="main-headline">
            7-Star Transformational Leadership<br>
             Rating System
        </h1>
        
        @guest
            <a href="{{ route('login') }}" class="cta-button">
                MULAI PENILAIAN SEKARANG
            </a>
        @endguest

        <div class="cards-container">
            <div class="glass-card">
                <div class="card-title">360°</div>
                <div class="card-desc">Multi-Rater Feedback</div>
            </div>

            <div class="glass-card">
                <div class="card-title">AI</div>
                <div class="card-desc">Powered Analysis</div>
            </div>

            <div class="glass-card">
                <img src="{{ asset('images/bintang.png') }}" alt="Star Icon" class="star-icon-card">
                <div class="card-desc">Star Rating System</div>
            </div>
        </div>
    </main>

    <footer>
        &copy; {{ date('Y') }} 7-Star. All rights reserved.
    </footer>

</body>
</html>