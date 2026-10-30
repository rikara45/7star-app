<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Token penilaian sudah selesai diisi; controller menerjemahkannya menjadi
 * pesan ke pengguna, bukan 403 dari dalam service.
 */
class AssessmentCompletedException extends RuntimeException {}
