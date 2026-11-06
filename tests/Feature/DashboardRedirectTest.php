<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_dosen_diarahkan_ke_dashboard_penilaian(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);

        $this->actingAs($dosen)
            ->get('/dashboard')
            ->assertRedirect(route('dashboard.index'));
    }

    public function test_admin_diarahkan_ke_dashboard_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_jurusan_diarahkan_ke_dashboard_jurusan(): void
    {
        $jurusan = User::factory()->create(['role' => 'jurusan']);

        $this->actingAs($jurusan)
            ->get('/dashboard')
            ->assertRedirect(route('jurusan.dashboard'));
    }

    public function test_tamu_diarahkan_ke_halaman_login(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }
}
