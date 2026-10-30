<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use App\Models\User;
use App\Support\PortfolioCategories;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Route jurusan memakai {userId} dan {portfolioId} mentah; tanpa pembatasan role
 * jurusan bisa membaca data admin/jurusan lain dan menilai portofolio siapa pun.
 */
class JurusanAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_jurusan_tidak_bisa_membuka_data_non_dosen(): void
    {
        $jurusan = User::factory()->create(['role' => 'jurusan']);
        $admin = User::factory()->create(['role' => 'admin']);
        $jurusanLain = User::factory()->create(['role' => 'jurusan']);

        foreach ([$admin, $jurusanLain] as $target) {
            $this->actingAs($jurusan)
                ->get(route('jurusan.verifikasi.show', $target->id))
                ->assertNotFound();

            $this->actingAs($jurusan)
                ->get(route('jurusan.result', $target->id))
                ->assertNotFound();

            $this->actingAs($jurusan)
                ->get(route('jurusan.result.pdf', $target->id))
                ->assertNotFound();
        }
    }

    public function test_jurusan_tidak_bisa_menilai_portofolio_non_dosen(): void
    {
        $jurusan = User::factory()->create(['role' => 'jurusan']);
        $admin = User::factory()->create(['role' => 'admin']);

        $portfolio = Portfolio::create([
            'user_id' => $admin->id,
            'category' => array_key_first(PortfolioCategories::all()),
            'file_path' => 'portfolios/admin.pdf',
            'status' => 'uploaded',
        ]);

        $this->actingAs($jurusan)
            ->post(route('jurusan.nilai.store', $portfolio->id), ['score' => 9])
            ->assertNotFound();

        $this->assertSame('uploaded', $portfolio->fresh()->status);
        $this->assertEquals(0, $portfolio->fresh()->score);
    }
}
