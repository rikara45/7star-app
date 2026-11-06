<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use App\Models\User;
use App\Support\PortfolioCategories;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JurusanVerificationFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_filter_pending_hanya_menampilkan_dosen_dengan_bukti_belum_diverifikasi(): void
    {
        $jurusan = User::factory()->create(['role' => 'jurusan']);
        $punyaBukti = User::factory()->create(['role' => 'dosen', 'name' => 'Dosen Punya Bukti']);
        $tanpaBukti = User::factory()->create(['role' => 'dosen', 'name' => 'Dosen Tanpa Bukti']);

        Portfolio::create([
            'user_id' => $punyaBukti->id,
            'category' => array_key_first(PortfolioCategories::all()),
            'file_path' => 'portfolios/bukti.pdf',
            'status' => 'uploaded',
        ]);

        $this->actingAs($jurusan)
            ->get(route('jurusan.verifikasi.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee('Dosen Punya Bukti')
            ->assertDontSee('Dosen Tanpa Bukti');
    }

    public function test_tanpa_filter_semua_dosen_tampil(): void
    {
        $jurusan = User::factory()->create(['role' => 'jurusan']);
        User::factory()->create(['role' => 'dosen', 'name' => 'Dosen Tanpa Bukti']);

        $this->actingAs($jurusan)
            ->get(route('jurusan.verifikasi.index'))
            ->assertOk()
            ->assertSee('Dosen Tanpa Bukti');
    }
}
