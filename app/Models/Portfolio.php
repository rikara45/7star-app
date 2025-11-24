<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Portfolio extends Model
{
    // Bagian ini yang kurang tadi.
    // Kita memberitahu Laravel bahwa kolom-kolom ini AMAN untuk diisi otomatis.
    protected $fillable = [
        'user_id',
        'category',
        'file_path',
        'description',
        'score',
        'status',
    ];

    // Relasi balik ke User (Opsional, tapi bagus ada)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}