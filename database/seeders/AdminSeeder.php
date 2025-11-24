<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Super Administrator',
            'email' => 'admin@univ.ac.id', // Email Login Admin
            'password' => Hash::make('password'), // Password: password
            'role' => 'admin',
            'jabatan' => 'Administrator Sistem',
            'unit_kerja' => 'Pusat TI',
        ]);
        
        // Opsional: Buat 1 Akun Jurusan Dummy
        User::create([
            'name' => 'Kaprodi Teknik',
            'email' => 'jurusan@univ.ac.id',
            'password' => Hash::make('password'),
            'role' => 'jurusan',
            'jabatan' => 'Ketua Program Studi',
            'unit_kerja' => 'Fakultas Teknik',
        ]);
    }
}