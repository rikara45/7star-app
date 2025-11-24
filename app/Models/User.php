<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail; // Uncomment jika butuh verifikasi email
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // Jika pakai Sanctum, jika error baris ini bisa dihapus/komentar

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'jabatan',
        'unit_kerja',
        'role', // <--- Tambahkan ini
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // --- RELASI TAMBAHAN 7 BINTANG ---

    // 1. Relasi ke Portofolio
    public function portfolios()
    {
        return $this->hasMany(Portfolio::class);
    }

    // 2. Relasi ke Request Penilaian (Sebagai Target)
    public function assessmentRequests()
    {
        return $this->hasMany(AssessmentRequest::class);
    }

    // 3. Relasi ke Hasil Analisis AI
    public function aiAnalyses()
    {
        return $this->hasMany(AiAnalysis::class);
    }
}