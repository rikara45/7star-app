<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" href="{{ asset('images/bintang.png') }}" type="image/png">
    
    <title>{{ config('app.name', 'Laravel') }}</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            /* Background Deep Blue Radial */
            background: radial-gradient(circle at 50% -20%, #0f1e45 0%, #020617 60%, #000000 100%);
            font-family: 'Poppins', sans-serif;
            color: white;
        }
        
        /* Kartu Kaca (Glassmorphism) */
        .glass-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 20px 40px rgba(0,0,0,0.6);
        }

        /* Override autofill browser */
        input:-webkit-autofill,
        input:-webkit-autofill:hover, 
        input:-webkit-autofill:focus, 
        input:-webkit-autofill:active{
            -webkit-box-shadow: 0 0 0 30px #0b1121 inset !important;
            -webkit-text-fill-color: white !important;
            transition: background-color 5000s ease-in-out 0s;
        }
    </style>
</head>
<body class="antialiased min-h-screen flex flex-col sm:justify-center items-center pt-10 sm:pt-0">
    
    <div class="mb-8">
        <a href="/">
            <img src="{{ asset('images/logo-7star.png') }}" alt="Logo" class="h-24 w-auto drop-shadow-xl hover:scale-105 transition duration-300">
        </a>
    </div>

    <div class="w-full sm:max-w-md px-8 py-10 glass-card sm:rounded-3xl overflow-hidden relative z-10 mb-10">
        {{ $slot }}
    </div>
    
    
</body>
</html>