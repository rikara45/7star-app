<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_registrasi_tidak_tersedia(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_pendaftaran_mandiri_tidak_membuat_akun(): void
    {
        $this->post('/register', [
            'name' => 'Penyusup',
            'email' => 'penyusup@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'penyusup@example.com']);
        $this->assertGuest();
    }
}
