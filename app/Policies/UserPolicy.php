<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Satu-satunya aturan: hanya admin yang boleh mengelola user.
     */
    public function manage(User $user): bool
    {
        return $user->role === 'admin';
    }
}
