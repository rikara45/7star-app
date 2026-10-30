<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use App\Models\User;
use App\Support\PortfolioCategories;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JurusanViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_daftar_verifikasi_memakai_badge_komponen(): void
    {
        $jurusan = User::factory()->create(['role' => 'jurusan']);
        $categories = array_keys(PortfolioCategories::all());

        User::factory()->create(['role' => 'dosen', 'name' => 'Dosen Kosong']);
        $belum = User::factory()->create(['role' => 'dosen', 'name' => 'Dosen Pending']);
        $selesai = User::factory()->create(['role' => 'dosen', 'name' => 'Dosen Selesai']);

        Portfolio::create([
            'user_id' => $belum->id,
            'category' => $categories[0],
            'file_path' => 'portfolios/pending.pdf',
            'status' => 'uploaded',
        ]);

        Portfolio::create([
            'user_id' => $selesai->id,
            'category' => $categories[0],
            'file_path' => 'portfolios/verified.pdf',
            'status' => 'verified',
            'score' => 8,
        ]);

        $this->actingAs($jurusan)
            ->get(route('jurusan.verifikasi.index'))
            ->assertOk()
            ->assertSee('Belum Mengirim')
            ->assertSee('1 Perlu Verifikasi')
            ->assertSee('Selesai')
            // Warna badge berasal dari x-status-badge, bukan kelas lepas.
            ->assertSee('bg-red-100 text-red-800 border-red-200', false)
            ->assertSee('bg-yellow-100 text-yellow-800 border-yellow-200', false)
            ->assertSee('bg-green-200 text-green-800 border-green-200', false);
    }

    public function test_detail_verifikasi_memakai_badge_komponen(): void
    {
        $jurusan = User::factory()->create(['role' => 'jurusan']);
        $dosen = User::factory()->create(['role' => 'dosen']);
        $categories = array_keys(PortfolioCategories::all());

        Portfolio::create([
            'user_id' => $dosen->id,
            'category' => $categories[0],
            'file_path' => 'portfolios/verified.pdf',
            'status' => 'verified',
            'score' => 7.5,
        ]);

        Portfolio::create([
            'user_id' => $dosen->id,
            'category' => $categories[1],
            'file_path' => 'portfolios/pending.pdf',
            'status' => 'uploaded',
        ]);

        $this->actingAs($jurusan)
            ->get(route('jurusan.verifikasi.show', $dosen->id))
            ->assertOk()
            ->assertSee('Verified (Skor:', false)
            ->assertSee('score-val', false)
            ->assertSee('Perlu Dinilai')
            ->assertSee('Kosong')
            ->assertSee('bg-green-200 text-green-800 border-green-200', false)
            ->assertSee('bg-yellow-100 text-yellow-800 border-yellow-200', false)
            ->assertSee('bg-gray-100 text-gray-600 border-gray-200', false)
            // Ikon lonceng dekoratif diganti teks status.
            ->assertDontSee('🔔');
    }
}
