<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('assessment.seed.admin_email');
        $password = config('assessment.seed.admin_password');

        if (! $email || ! $password) {
            return;
        }

        if (app()->environment('production') && $password === 'password') {
            throw new RuntimeException(
                'SEED_ADMIN_PASSWORD masih memakai nilai default di production. Set password kuat sebelum menjalankan seeder.'
            );
        }

        User::firstOrCreate(
            ['email' => $email],
            [
                'name' => config('assessment.seed.admin_name'),
                'password' => Hash::make($password),
                'role' => 'admin',
                'jabatan' => 'Administrator Sistem',
                'unit_kerja' => 'Pusat TI',
            ]
        );

        $jurusanEmail = config('assessment.seed.jurusan_email');
        $jurusanPassword = config('assessment.seed.jurusan_password');

        if (! $jurusanEmail || ! $jurusanPassword) {
            return;
        }

        User::firstOrCreate(
            ['email' => $jurusanEmail],
            [
                'name' => config('assessment.seed.jurusan_name'),
                'password' => Hash::make($jurusanPassword),
                'role' => 'jurusan',
                'jabatan' => 'Ketua Program Studi',
                'unit_kerja' => 'Fakultas',
            ]
        );
    }
}
