<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'dosen',
        ]);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'role' => 'dosen',
        ]);
        $response->assertRedirect(route('dashboard.index', absolute: false));
    }

    public function test_form_registrasi_tidak_menawarkan_peran_jurusan(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertDontSee('Pihak Jurusan');
    }

    public function test_peran_jurusan_dari_form_publik_diabaikan(): void
    {
        $response = $this->post('/register', [
            'name' => 'Jurusan User',
            'email' => 'jurusan@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'jurusan',
        ]);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'jurusan@example.com',
            'role' => 'dosen',
        ]);
        $response->assertRedirect(route('dashboard.index', absolute: false));
    }
}
