<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentRequest extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    // Relasi ke User yang dinilai
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke Jawaban
    public function responses()
    {
        return $this->hasMany(AssessmentResponse::class);
    }

    // Cek apakah sudah selesai dinilai
    public function isCompleted()
    {
        return $this->is_completed;
    }
}
