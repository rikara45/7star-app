<?php

return [
    'invitation' => [
        // Nilai env kosong ("") atau 0 jatuh ke default, bukan ke batas bawah service.
        'max_superior' => (int) env('ASSESSMENT_MAX_SUPERIOR', 2) ?: 2,
        'max_peer' => (int) env('ASSESSMENT_MAX_PEER', 5) ?: 5,
        'token_length' => (int) env('ASSESSMENT_TOKEN_LENGTH', 32) ?: 32,
    ],
];
