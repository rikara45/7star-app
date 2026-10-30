<?php

namespace App\Policies;

use App\Models\Portfolio;
use App\Models\User;

class PortfolioPolicy
{
    public function download(User $user, Portfolio $portfolio): bool
    {
        return $portfolio->user_id === $user->id
            || in_array($user->role, ['jurusan', 'admin'], true);
    }
}
