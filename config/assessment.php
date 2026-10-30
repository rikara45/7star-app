<?php

return [
    'invitation' => [
        'max_superior' => (int) env('ASSESSMENT_MAX_SUPERIOR', 2),
        'max_peer' => (int) env('ASSESSMENT_MAX_PEER', 5),
        'token_length' => (int) env('ASSESSMENT_TOKEN_LENGTH', 32),
    ],
];
