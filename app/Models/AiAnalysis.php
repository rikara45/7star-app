<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiAnalysis extends Model
{
    // Tambahkan ini agar data bisa disimpan otomatis
    protected $fillable = [
        'user_id',
        'assessment_period_id',
        'score_behavior',
        'score_portfolio',
        'final_score',
        'star_rating',
        'ai_narrative',
        'ai_recommendations' // (Opsional jika nanti dipakai)
    ];

    // Relasi ke User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke Periode
    public function assessmentPeriod()
    {
        return $this->belongsTo(AssessmentPeriod::class);
    }
}