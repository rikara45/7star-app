<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortfolioDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_pemilik_bisa_mengunduh_berkas_privat(): void
    {
        $portfolio = $this->portfolioOn('local');

        $this->actingAs($portfolio->user)
            ->get(route('portfolio.file', $portfolio))
            ->assertOk();
    }

    public function test_jurusan_dan_admin_bisa_mengunduh_berkas(): void
    {
        $portfolio = $this->portfolioOn('local');

        foreach (['jurusan', 'admin'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('portfolio.file', $portfolio))
                ->assertOk();
        }
    }

    public function test_dosen_lain_tidak_boleh_mengunduh(): void
    {
        $portfolio = $this->portfolioOn('local');

        $this->actingAs(User::factory()->create(['role' => 'dosen']))
            ->get(route('portfolio.file', $portfolio))
            ->assertForbidden();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $portfolio = $this->portfolioOn('local');

        $this->get(route('portfolio.file', $portfolio))
            ->assertRedirect(route('login'));
    }

    public function test_berkas_lama_di_disk_publik_tetap_bisa_diunduh(): void
    {
        $portfolio = $this->portfolioOn('public');

        $this->actingAs($portfolio->user)
            ->get(route('portfolio.file', $portfolio))
            ->assertOk();
    }

    public function test_berkas_yang_tidak_ada_di_kedua_disk_menghasilkan_404(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $dosen = User::factory()->create(['role' => 'dosen']);
        $portfolio = Portfolio::create([
            'user_id' => $dosen->id,
            'category' => 'Kinerja Akademik',
            'file_path' => 'portfolios/hilang.pdf',
            'status' => 'uploaded',
            'score' => 0,
        ]);

        $this->actingAs($dosen)
            ->get(route('portfolio.file', $portfolio))
            ->assertNotFound();
    }

    private function portfolioOn(string $disk): Portfolio
    {
        Storage::fake('local');
        Storage::fake('public');

        $dosen = User::factory()->create(['role' => 'dosen']);
        $path = 'portfolios/bukti-'.$disk.'.pdf';

        Storage::disk($disk)->put($path, '%PDF-1.4 contoh');

        return Portfolio::create([
            'user_id' => $dosen->id,
            'category' => 'Kinerja Akademik',
            'file_path' => $path,
            'status' => 'uploaded',
            'score' => 0,
        ]);
    }
}
