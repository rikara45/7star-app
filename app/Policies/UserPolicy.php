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

    /**
     * Admin tidak boleh menghapus akunnya sendiri. Di shared hosting tanpa SSH,
     * kehilangan admin terakhir berarti pemulihan harus lewat database.
     */
    public function delete(User $user, User $target): bool
    {
        return $user->role === 'admin' && ! $user->is($target);
    }
}
