<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UniversitySetting extends Model
{
    protected $guarded = ['id'];

    /**
     * Baris pengaturan bisa belum ada bila tabel dibersihkan manual. Laporan
     * harus tetap bisa dicetak dengan kop seadanya, bukan error 500.
     */
    public static function current(): self
    {
        return static::query()->first() ?? new static([
            'uni_name' => 'Universitas',
            'uni_address' => '',
            'uni_website' => '',
            'uni_logo_path' => null,
        ]);
    }
}
