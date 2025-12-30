<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>7-Star Transformational Leadership Rating System</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>

        .gold-gradient {
            background: linear-gradient(135deg, #FFD700 0%, #FDB931 100%); /* Gradasi Emas */
            color: #1a202c; /* Warna teks gelap (Royal 900 approximation) */
        }
        .text-royal-900 {
            color: #0f172a; /* Biru sangat gelap */
        }
        /* Efek Hover agar makin berkilau */
        .gold-gradient:hover {
            background: linear-gradient(135deg, #FFE55C 0%, #FDC830 100%);
        }
        body {
            font-family: 'Inter', sans-serif;
        }
        .font-display {
            font-family: 'Playfair Display', serif;
        }
        .gold-gradient {
            background: linear-gradient(135deg, #D4AF37 0%, #F9D976 50%, #D4AF37 100%);
        }
        .royal-gradient {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
        }
        .hero-overlay {
            background: linear-gradient(135deg, rgba(30, 58, 138, 0.95) 0%, rgba(59, 130, 246, 0.85) 100%);
        }
        .star-shimmer {
            animation: shimmer 2s infinite;
        }
        @keyframes shimmer {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
    </style>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'royal': {
                            900: '#1e3a8a',
                            800: '#1e40af',
                            700: '#1d4ed8',
                            600: '#2563eb',
                        },
                        'gold': {
                            500: '#D4AF37',
                            600: '#B8960F',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="antialiased bg-gray-50">
    
    <nav class="fixed w-full z-50 transition-all duration-300 bg-transparent" id="navbar">
        <div class="container mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <a href="/" class="flex items-center space-x-2">
                    <div class="flex items-center">
                        <i class="fas fa-star text-yellow-400 text-2xl star-shimmer"></i>
                        <span class="ml-2 text-xl font-bold text-white font-display">7-Star Leadership</span>
                    </div>
                </a>
                
                <div class="hidden md:flex items-center space-x-4">
                    @if (Route::has('login'))
                        <nav class="-mx-3 flex flex-1 justify-end gap-2">
                            @auth
                                {{-- LOGIKA PENENTUAN ARAH DASHBOARD --}}
                                @php
                                    $targetRoute = 'dashboard.index'; 
                                    if (Auth::user()->role === 'admin') $targetRoute = 'admin.dashboard';
                                    elseif (Auth::user()->role === 'jurusan') $targetRoute = 'jurusan.dashboard';
                                @endphp

                                <a
                                    href="{{ route($targetRoute) }}"
                                    class="inline-flex items-center px-4 py-2 bg-white/10 backdrop-blur-sm border border-white/20 rounded-md font-semibold text-xs text-white uppercase tracking-widest shadow-sm hover:bg-white hover:text-gray-900 transition ease-in-out duration-150"
                                >
                                    Dashboard
                                </a>
                            @else
                                <a
                                    href="{{ route('login') }}"
                                    class="inline-flex items-center px-4 py-2 bg-white/10 backdrop-blur-sm border border-white/50 rounded-md font-semibold text-xs text-white uppercase tracking-widest shadow-sm hover:bg-white hover:text-gray-900 transition ease-in-out duration-150"
                                >
                                    Masuk
                                </a>

                                @if (Route::has('register'))
                                    <a
                                        href="{{ route('register') }}"
                                        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        Daftar
                                    </a>
                                @endif 
                            @endauth
                        </nav>
                    @endif
                </div>
                
                <button class="md:hidden text-white focus:outline-none" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
            </div>
        </div>
        
        <div id="mobileMenu" class="hidden md:hidden bg-gray-900 border-t border-gray-800 absolute w-full left-0 top-full shadow-xl">
            <div class="container mx-auto px-6 py-4 space-y-4">
                @if (Route::has('login'))
                    @auth
                        @php
                            $targetRoute = 'dashboard.index'; 
                            if (Auth::user()->role === 'admin') $targetRoute = 'admin.dashboard';
                            elseif (Auth::user()->role === 'jurusan') $targetRoute = 'jurusan.dashboard';
                        @endphp
                        <a href="{{ route($targetRoute) }}" class="block w-full text-center bg-white text-gray-700 px-4 py-2 rounded-md font-bold text-sm uppercase tracking-widest shadow-sm hover:bg-gray-50">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="block w-full text-center bg-white text-gray-700 px-4 py-2 rounded-md font-bold text-sm uppercase tracking-widest shadow-sm hover:bg-gray-50">
                            Masuk
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="block w-full text-center bg-red-600 text-white px-4 py-2 rounded-md font-bold text-sm uppercase tracking-widest shadow-sm hover:bg-red-700">
                                Daftar
                            </a>
                        @endif
                    @endauth
                @endif
            </div>
        </div>
    </nav>
    
    <!-- Hero Section -->
    <section class="relative min-h-screen flex items-center justify-center overflow-hidden">
        <!-- Background Image with Overlay -->
        <div class="absolute inset-0 z-0">
            <img src="https://images.unsplash.com/photo-1562774053-701939374585?q=80&w=2048" 
                 alt="University Building" 
                 class="w-full h-full object-cover">
            <div class="absolute inset-0 hero-overlay"></div>
        </div>
        
        <!-- Decorative Elements -->
        <div class="absolute top-20 right-10 text-yellow-400/20 hidden lg:block">
            <i class="fas fa-star text-8xl"></i>
        </div>
        <div class="absolute bottom-20 left-10 text-yellow-400/20 hidden lg:block">
            <i class="fas fa-award text-6xl"></i>
        </div>
        
        <!-- Content -->
        <div class="container mx-auto px-6 relative z-10 text-center text-white pt-20">
            <div class="max-w-4xl mx-auto">
                <!-- Badge -->
                <div class="inline-flex items-center space-x-2 bg-yellow-400/20 backdrop-blur-sm border border-yellow-400/30 rounded-full px-6 py-2 mb-8">
                    <i class="fas fa-certificate text-yellow-400"></i>
                    <span class="text-sm font-semibold text-yellow-400">Transformational Leadership Assessment</span>
                </div>
                
                <!-- Headline -->
                <h1 class="text-4xl md:text-6xl lg:text-7xl font-bold mb-6 leading-tight font-display">
                    7-Star <span class="text-yellow-400">Transformational Leadership</span> Rating System
                </h1>
                
                <!-- CTA Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center mt-8 md:mt-20">
                @auth
                    {{-- 1. LOGIKA JIKA SUDAH LOGIN --}}
                    @php
                        $targetRoute = 'dashboard.index'; // Default Dosen
                        if (Auth::user()->role === 'admin') {
                            $targetRoute = 'admin.dashboard';
                        } elseif (Auth::user()->role === 'jurusan') {
                            $targetRoute = 'jurusan.dashboard';
                        }
                    @endphp

                    <a href="{{ route($targetRoute) }}" 
                       class="gold-gradient text-royal-900 px-8 py-4 rounded-xl font-bold text-lg hover:shadow-2xl hover:scale-105 transition-all inline-flex items-center space-x-2">
                        <span>Lanjutkan ke Dashboard ({{ ucfirst(Auth::user()->role) }})</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                @else
                    {{-- 2. LOGIKA JIKA BELUM LOGIN (TAMU) --}}
                    <a href="{{ route('login') }}" 
                       class="gold-gradient text-royal-900 px-8 py-4 rounded-xl font-bold text-lg hover:shadow-2xl hover:scale-105 transition-all inline-flex items-center space-x-2">
                        <span>Mulai Penilaian Sekarang</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                @endauth
            </div>

                </div>
                
                <!-- Stats -->
                <div class="grid grid-cols-3 gap-6 mt-16 max-w-2xl mx-auto">
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6 border border-white/20">
                        <div class="text-3xl font-bold text-yellow-400 mb-2">360°</div>
                        <div class="text-sm text-gray-200">Multi-Rater Feedback</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6 border border-white/20">
                        <div class="text-3xl font-bold text-yellow-400 mb-2">AI</div>
                        <div class="text-sm text-gray-200">Powered Analysis</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6 border border-white/20">
                        <div class="text-3xl font-bold text-yellow-400 mb-2">7★</div>
                        <div class="text-sm text-gray-200">Star Rating System</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Scroll Indicator -->
        <div class="absolute bottom-10 left-1/2 transform -translate-x-1/2 animate-bounce">
            <i class="fas fa-chevron-down text-white text-2xl opacity-50"></i>
        </div>
    </section>
    
    <!-- Footer Section -->
    <footer class="bg-gray-900 text-gray-400 py-8">
        <div class="container mx-auto px-6">
            <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
                <!-- Logo & Copyright -->
                <div class="flex items-center space-x-3">
                    <i class="fas fa-star text-yellow-400 text-xl star-shimmer"></i>
                    <div>
                        <div class="text-white font-bold font-display">7-Star Leadership</div>
                        <div class="text-xs text-gray-500">© 2025 All rights reserved</div>
                    </div>
                </div>
                
                <!-- Links -->
                <div class="flex items-center space-x-6 text-sm">
                    <a href="#" class="hover:text-yellow-400 transition">About</a>
                    <a href="#" class="hover:text-yellow-400 transition">Contact</a>
                    <a href="#" class="hover:text-yellow-400 transition">Privacy</a>
                </div>
                
                <!-- Social Media -->
                <div class="flex items-center space-x-4">
                    <a href="#" class="text-gray-400 hover:text-yellow-400 transition">
                        <i class="fab fa-linkedin-in text-lg"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-yellow-400 transition">
                        <i class="fab fa-instagram text-lg"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-yellow-400 transition">
                        <i class="fab fa-twitter text-lg"></i>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script>
        // Mobile Menu Toggle
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            menu.classList.toggle('hidden');
        }

        // Navbar Background on Scroll
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('bg-royal-900', 'shadow-lg');
            } else {
                navbar.classList.remove('bg-royal-900', 'shadow-lg');
            }
        });

        // Smooth Scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                if (href === '#' || href === '') return;
                
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('mobileMenu');
            const button = event.target.closest('button');
            
            if (!menu.contains(event.target) && !button && !menu.classList.contains('hidden')) {
                menu.classList.add('hidden');
            }
        });
    </script>
</body>
</html>