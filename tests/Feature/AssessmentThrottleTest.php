<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Endpoint penilaian memakai token di URL dan terbuka tanpa login, jadi laju
 * aksesnya perlu dibatasi agar token tidak bisa ditebak berulang kali.
 */
class AssessmentThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_kirim_jawaban_dibatasi_dua_puluh_per_menit(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->post('/nilai/token-palsu', ['answers' => [1 => 3]])->assertNotFound();
        }

        $this->post('/nilai/token-palsu', ['answers' => [1 => 3]])->assertStatus(429);
    }
}
