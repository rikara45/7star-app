<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Portfolio extends Model
{
    // Bagian ini yang kurang tadi.
    // Kita memberitahu Laravel bahwa kolom-kolom ini AMAN untuk diisi otomatis.
    protected $fillable = [
        'user_id',
        'assessment_period_id',
        'category',
        'file_path',
        'description',
        'score',
        'status',
    ];

    /**
     * Portofolio baru otomatis masuk ke periode aktif supaya pemanggil yang
     * lupa mengisi tidak membuat baris yatim.
     */
    protected static function booted(): void
    {
        static::creating(function (Portfolio $portfolio) {
            if ($portfolio->assessment_period_id === null) {
                $portfolio->assessment_period_id = AssessmentPeriod::where('is_active', true)
                    ->orderBy('id')
                    ->value('id');
            }
        });
    }

    /**
     * Baris warisan (sebelum kolom periode ada) tetap ikut dihitung. Setelah
     * backfill produksi selesai, fallback ini hanya menyentuh data lama.
     */
    public function scopeForPeriod(Builder $query, ?int $periodId): Builder
    {
        if ($periodId === null) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($periodId) {
            $q->where('assessment_period_id', $periodId)
                ->orWhereNull('assessment_period_id');
        });
    }

    // Relasi balik ke User (Opsional, tapi bagus ada)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function period()
    {
        return $this->belongsTo(AssessmentPeriod::class, 'assessment_period_id');
    }
}
