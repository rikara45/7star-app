<?php

namespace App\Policies;

use App\Models\UniversitySetting;
use App\Models\User;

class UniversitySettingPolicy
{
    /**
     * Dipakai sebelum baris pengaturan disentuh, supaya tidak ada penulisan
     * apa pun (firstOrCreate) sebelum izin diperiksa.
     */
    public function manage(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, UniversitySetting $setting): bool
    {
        return $user->role === 'admin';
    }
}
