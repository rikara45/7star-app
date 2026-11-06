<?php

namespace Tests\Feature;

use App\Models\AssessmentPeriod;
use App\Models\Portfolio;
use App\Models\User;
use App\Services\AssessmentScoreService;
use App\Support\PortfolioCategories;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Portofolio adalah bukti per periode. Nilai periode lama tidak boleh ikut
 * terhitung atau tertimpa saat periode baru diaktifkan.
 */
class PortfolioPeriodTest extends TestCase
{
    use RefreshDatabase;

    public function test_skor_portofolio_hanya_menjumlah_bukti_periode_yang_diminta(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $periodeA = AssessmentPeriod::create(['name' => 'Periode A', 'is_active' => false]);
        $periodeB = AssessmentPeriod::create(['name' => 'Periode B', 'is_active' => true]);

        $this->bukti($dosen, $periodeA, 9);
        $this->bukti($dosen, $periodeB, 2);

        $service = app(AssessmentScoreService::class);

        $this->assertSame(9.0, (float) $service->calculate($dosen, $periodeA)['portfolioScore']);
        $this->assertSame(2.0, (float) $service->calculate($dosen, $periodeB)['portfolioScore']);
    }

    public function test_baris_warisan_tanpa_periode_tetap_dihitung(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $periode = AssessmentPeriod::create(['name' => 'Periode B', 'is_active' => true]);

        DB::table('portfolios')->insert([
            'user_id' => $dosen->id,
            'category' => array_key_first(PortfolioCategories::all()),
            'file_path' => 'portfolios/lama.pdf',
            'score' => 5,
            'status' => 'verified',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(AssessmentScoreService::class);

        $this->assertSame(5.0, (float) $service->calculate($dosen, $periode)['portfolioScore']);
    }

    public function test_unggahan_periode_baru_tidak_menimpa_bukti_periode_lama(): void
    {
        Storage::fake('local');

        $dosen = User::factory()->create(['role' => 'dosen']);
        $kategori = array_key_first(PortfolioCategories::all());
        $periodeA = AssessmentPeriod::create(['name' => 'Periode A', 'is_active' => true]);

        $this->actingAs($dosen)
            ->post(route('portfolio.store'), [
                'category' => $kategori,
                'file' => UploadedFile::fake()->create('bukti-a.pdf', 100, 'application/pdf'),
                'description' => 'Bukti A',
            ])
            ->assertSessionHas('success_portfolio');

        $periodeA->update(['is_active' => false]);
        $periodeB = AssessmentPeriod::create(['name' => 'Periode B', 'is_active' => true]);

        $this->actingAs($dosen)
            ->post(route('portfolio.store'), [
                'category' => $kategori,
                'file' => UploadedFile::fake()->create('bukti-b.pdf', 100, 'application/pdf'),
                'description' => 'Bukti B',
            ])
            ->assertSessionHas('success_portfolio');

        $this->assertSame(2, Portfolio::count());

        $buktiA = Portfolio::where('assessment_period_id', $periodeA->id)->firstOrFail();
        $buktiB = Portfolio::where('assessment_period_id', $periodeB->id)->firstOrFail();

        $this->assertSame('Bukti A', $buktiA->description);
        $this->assertSame('Bukti B', $buktiB->description);
        $this->assertNotSame($buktiA->file_path, $buktiB->file_path);
    }

    public function test_unggahan_ditolak_saat_tidak_ada_periode_aktif(): void
    {
        Storage::fake('local');

        $dosen = User::factory()->create(['role' => 'dosen']);

        $this->actingAs($dosen)
            ->post(route('portfolio.store'), [
                'category' => array_key_first(PortfolioCategories::all()),
                'file' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHas('error_portfolio');

        $this->assertSame(0, Portfolio::count());
        Storage::disk('local')->assertDirectoryEmpty('portfolios');
    }

    private function bukti(User $dosen, AssessmentPeriod $periode, int $skor): void
    {
        Portfolio::create([
            'user_id' => $dosen->id,
            'assessment_period_id' => $periode->id,
            'category' => array_key_first(PortfolioCategories::all()),
            'file_path' => 'portfolios/'.$periode->id.'.pdf',
            'score' => $skor,
            'status' => 'verified',
        ]);
    }
}
