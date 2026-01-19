<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="icon" href="{{ asset('images/bintang.png') }}" type="image/png">
    
    <title>7-Star Transformational Leadership Assessment</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    
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
            /* BACKGROUND DEEP BLUE */
            background: radial-gradient(circle at 50% -20%, #0f1e45 0%, #020617 60%, #000000 100%);
            color: white;
            overflow-x: hidden;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        /* --- LAYER 1: LIGHT GLOW --- */
        .glow-effect {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2; 
            background: radial-gradient(circle at 50% 50%, rgba(0, 123, 255, 0.05) 0%, transparent 50%);
            pointer-events: none;
        }

        /* --- LAYER 2: BACKGROUND WAVY LINES --- */
        .background-lines {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0; 
            pointer-events: none; 
            opacity: 0.3;
            overflow: hidden;
        }

        .background-lines svg {
            width: 100%;
            height: 100%;
        }

        /* --- NAVBAR --- */
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 40px 80px;
            width: 100%;
            z-index: 10;
            transition: all 0.3s ease;
        }

        .logo-container {
            display: flex;
            align-items: center;
        }

        .logo-img {
            height: 60px;
            width: auto;
            object-fit: contain;
            display: block;
        }

        /* --- TOMBOL NAVBAR --- */
        .nav-buttons {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .btn {
            padding: 10px 32px;
            border-radius: 6px; 
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-login {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
        }
        
        .btn-login:hover {
            border-color: white;
            background: rgba(255, 255, 255, 0.1);
        }

        .btn-register {
            background: linear-gradient(90deg, #d64732 0%, #b52b19 100%);
            border: none;
            color: white;
        }

        .btn-register:hover {
            filter: brightness(1.1);
            box-shadow: 0 0 15px rgba(214, 71, 50, 0.4);
        }

        /* --- HERO SECTION (Perbaikan Posisi Disini) --- */
        .hero {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center; /* Pastikan konten di tengah secara vertikal */
            text-align: center;
            
            /* SAYA HAPUS 'margin-top: -40px' DAN GANTI DENGAN INI: */
            padding-top: 40px;    /* Memberi jarak dari Navbar */
            padding-bottom: 60px; /* Memberi jarak dari bawah layar */
            
            z-index: 5; 
            padding-left: 20px;
            padding-right: 20px;
        }

        .sub-headline {
            font-size: 1.1rem;
            font-weight: 300;
            color: #d0d0d0;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
        }

        /* JUDUL: Jarak ke tombol diatur disini (margin-bottom) */
        .main-headline {
            font-size: 3.2rem;
            font-weight: 600;
            line-height: 1.2;
            margin-bottom: 50px; /* Jarak judul ke tombol */
            text-shadow: 0 4px 20px rgba(0,0,0,0.5);
            max-width: 1200px; 
            width: 100%;
        }

        /* --- TOMBOL UTAMA (CTA) --- */
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
            
            /* Jarak Tombol ke Kartu Bawah */
            margin-bottom: 80px; /* Tidak terlalu jauh, tidak terlalu dekat */
            
            line-height: 1;
            text-decoration: none;
        }

        .cta-button:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: #fff;
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.15);
        }

        /* --- CARDS SECTION --- */
        .cards-container {
            display: flex;
            gap: 25px;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
        }

        .card {
            width: 240px;
            height: 150px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            backdrop-filter: blur(10px); 
            -webkit-backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.07);
        }

        .card-title {
            font-size: 2.5rem;
            font-weight: 600;
            color: #3b82f6; 
            line-height: 1;
        }

        .card-desc {
            font-size: 0.8rem;
            color: #cbd5e1;
            font-weight: 400;
            letter-spacing: 0.3px;
        }

        .star-icon-card {
            width: 55px;
            height: 55px;
            object-fit: contain;
            display: block;
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
                gap: 10px;
            }

            .btn {
                padding: 10px 24px;
                font-size: 0.85rem;
                flex: 1; 
            }

            /* Di HP, jaraknya disesuaikan lagi */
            .hero {
                padding-top: 20px;
                padding-bottom: 40px;
            }

            .main-headline { 
                font-size: 1.8rem; 
                margin-bottom: 40px;
                max-width: 100%;
            }

            .cta-button {
                width: 100%;
                max-width: 320px;
                padding: 15px 20px;
                font-size: 0.9rem;
                text-align: center;
                margin-bottom: 60px; /* Jarak ke kartu di HP lebih kecil */
            }

            .sub-headline {
                font-size: 0.9rem;
            }

            .cards-container { 
                flex-direction: column; 
                width: 100%;
            }
            
            .card { 
                width: 100%; 
                max-width: 320px; 
            }

            .logo-img { 
                height: 50px; 
            }
        }
    </style>
</head>
<body>

    <div class="glow-effect"></div>

    <div class="background-lines">
        <svg viewBox="0 0 1440 900" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
            <g opacity="0.5">
                <path d="M-100 200 C 200 400, 600 100, 1500 400" stroke="url(#line_grad1)" stroke-width="1.5"/>
                <path d="M-200 500 C 300 900, 1000 200, 1600 600" stroke="url(#line_grad2)" stroke-width="2"/>
                <path d="M0 800 C 400 1000, 1100 600, 1500 800" stroke="url(#line_grad1)" stroke-width="1.5"/>
            </g>
            <defs>
                <linearGradient id="line_grad1" x1="0%" y1="0%" x2="100%" y2="0%">
                    <stop offset="0%" stop-color="rgba(0, 80, 200, 0)"/>
                    <stop offset="50%" stop-color="rgba(100, 200, 255, 0.5)"/>
                    <stop offset="100%" stop-color="rgba(0, 80, 200, 0)"/>
                </linearGradient>
                <linearGradient id="line_grad2" x1="0%" y1="0%" x2="100%" y2="0%">
                    <stop offset="0%" stop-color="rgba(50, 150, 255, 0)"/>
                    <stop offset="40%" stop-color="rgba(150, 220, 255, 0.7)"/>
                    <stop offset="100%" stop-color="rgba(50, 150, 255, 0)"/>
                </linearGradient>
            </defs>
        </svg>
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
                    <a href="{{ url('/dashboard') }}" class="btn btn-login">DASHBOARD</a>
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
        
        <a href="{{ route('login') }}" class="cta-button">
            MULAI PENILAIAN SEKARANG
        </a>

        <div class="cards-container">
            <div class="card">
                <div class="card-title">360°</div>
                <div class="card-desc">Multi-Rater Feedback</div>
            </div>

            <div class="card">
                <div class="card-title">AI</div>
                <div class="card-desc">Powered Analysis</div>
            </div>

            <div class="card">
                <img src="{{ asset('images/bintang.png') }}" alt="Star Icon" class="star-icon-card">
                <div class="card-desc">Star Rating System</div>
            </div>
        </div>
    </main>

</body>
</html>