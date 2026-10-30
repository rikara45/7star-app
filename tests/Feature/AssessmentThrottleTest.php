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

    public function test_batas_membuka_form_tidak_memakan_kuota_kirim_jawaban(): void
    {
        // Penilai sah membuka form berkali-kali (refresh, kembali, dsb).
        for ($i = 0; $i < 20; $i++) {
            $this->get('/nilai/token-palsu')->assertNotFound();
        }

        $this->post('/nilai/token-palsu', ['answers' => [1 => 3]])->assertNotFound();
    }

    public function test_kuota_kirim_jawaban_dihitung_per_token(): void
    {
        // Satu IP kampus bisa dipakai banyak penilai; token lain tidak boleh
        // ikut terblokir karena satu form diisi berulang.
        for ($i = 0; $i < 20; $i++) {
            $this->post('/nilai/token-a', ['answers' => [1 => 3]])->assertNotFound();
        }

        $this->post('/nilai/token-a', ['answers' => [1 => 3]])->assertStatus(429);
        $this->post('/nilai/token-b', ['answers' => [1 => 3]])->assertNotFound();
    }

    public function test_tebak_token_beruntun_tetap_dibatasi_per_ip(): void
    {
        // Kuota per token tidak boleh membuat percobaan menebak banyak token lolos.
        for ($i = 1; $i <= 120; $i++) {
            $this->post('/nilai/tebakan-'.$i, ['answers' => [1 => 3]])->assertNotFound();
        }

        $this->post('/nilai/tebakan-121', ['answers' => [1 => 3]])->assertStatus(429);
    }
}
