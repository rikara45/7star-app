<?php

namespace App\Policies;

use App\Models\UniversitySetting;
use App\Models\User;

class UniversitySettingPolicy
{
    public function update(User $user, UniversitySetting $setting): bool
    {
        return $user->role === 'admin';
    }
}
