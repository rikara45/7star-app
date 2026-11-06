<?php

return [
    'invitation' => [
        // Nilai env kosong ("") atau 0 jatuh ke default, bukan ke batas bawah service.
        'max_superior' => (int) env('ASSESSMENT_MAX_SUPERIOR', 2) ?: 2,
        'max_peer' => (int) env('ASSESSMENT_MAX_PEER', 5) ?: 5,
        'token_length' => (int) env('ASSESSMENT_TOKEN_LENGTH', 32) ?: 32,
    ],

    'seed' => [
        'admin_name' => env('SEED_ADMIN_NAME', 'Super Administrator'),
        'admin_email' => env('SEED_ADMIN_EMAIL'),
        'admin_password' => env('SEED_ADMIN_PASSWORD'),
        'jurusan_name' => env('SEED_JURUSAN_NAME', 'Ketua Program Studi'),
        'jurusan_email' => env('SEED_JURUSAN_EMAIL'),
        'jurusan_password' => env('SEED_JURUSAN_PASSWORD'),
        'period_name' => env('SEED_PERIOD_NAME') ?: 'Periode '.date('Y'),
    ],
];
