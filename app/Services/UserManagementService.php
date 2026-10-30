<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Pembuatan dan perubahan user oleh admin. Validasi ada di FormRequest;
 * di sini hanya penyimpanan dan aturan password.
 */
class UserManagementService
{
    /**
     * @param  array{name: string, email: string, password: string, role: string, jabatan?: string|null}  $data
     */
    public function create(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'jabatan' => $data['jabatan'] ?? null,
        ]);
    }

    /**
     * Password hanya diganti bila diisi, supaya admin bisa memperbaiki data
     * tanpa memaksa reset password pengguna.
     *
     * @param  array{name: string, email: string, role: string, password?: string|null, jabatan?: string|null}  $data
     */
    public function update(User $user, array $data): User
    {
        $this->pastikanAdminTersisa($user, $data['role']);

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'jabatan' => $data['jabatan'] ?? null,
        ];

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $user->update($payload);

        return $user;
    }

    /**
     * Menurunkan role admin terakhir mengunci seluruh panel admin; di shared
     * hosting tanpa SSH pemulihannya harus lewat database.
     */
    private function pastikanAdminTersisa(User $user, string $roleBaru): void
    {
        if ($user->role !== 'admin' || $roleBaru === 'admin') {
            return;
        }

        $adaAdminLain = User::where('role', 'admin')
            ->whereKeyNot($user->getKey())
            ->exists();

        if (! $adaAdminLain) {
            throw ValidationException::withMessages([
                'role' => 'Tidak bisa mengubah role admin terakhir. Tambahkan admin lain terlebih dahulu.',
            ]);
        }
    }
}
