<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL; // <--- JANGAN LUPA IMPORT INI
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Throttle bawaan memakai kunci "domain|ip" untuk tamu, sehingga batas
        // GET dan POST saling memakan. Penilaian dibuka tanpa login dan sering
        // diakses beberapa penilai dari satu IP kampus, jadi kuncinya per token.
        RateLimiter::for('assessment-form', fn (Request $request) => Limit::perMinute(60)
            ->by((string) $request->route('token').'|'.$request->ip()));

        RateLimiter::for('assessment-submit', fn (Request $request) => [
            Limit::perMinute(20)->by((string) $request->route('token').'|'.$request->ip()),
            // Batas per IP tetap dipertahankan agar tebak-token beruntun tidak
            // lolos hanya karena setiap token punya kuotanya sendiri.
            Limit::perMinute(120)->by('ip|'.$request->ip()),
        ]);

        // Tambahkan logika ini:
        // Jika aplikasi mendeteksi sedang diakses lewat Ngrok (atau HTTPS), paksa semua link jadi HTTPS
        if ($this->app->environment('production') || str_contains(request()->url(), 'ngrok-free.app')) {
            URL::forceScheme('https');
        }
    }
}
