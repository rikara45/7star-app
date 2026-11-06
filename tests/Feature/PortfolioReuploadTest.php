<?php

namespace Tests\Feature;

use App\Models\AssessmentPeriod;
use App\Models\Portfolio;
use App\Models\User;
use App\Support\PortfolioCategories;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Nilai portofolio yang sudah diverifikasi jurusan adalah bagian dari skor akhir.
 * Mengunggah ulang tidak boleh mengembalikannya ke status belum dinilai.
 */
class PortfolioReuploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_unggahan_ulang_ditolak_setelah_portofolio_diverifikasi(): void
    {
        Storage::fake('local');

        $dosen = User::factory()->create(['role' => 'dosen']);
        $kategori = array_key_first(PortfolioCategories::all());

        $portfolio = Portfolio::create([
            'user_id' => $dosen->id,
            'category' => $kategori,
            'file_path' => 'portfolios/lama.pdf',
            'description' => 'Bukti lama',
            'score' => 9,
            'status' => 'verified',
        ]);

        $this->actingAs($dosen)
            ->post(route('portfolio.store'), [
                'category' => $kategori,
                'file' => UploadedFile::fake()->create('baru.pdf', 100, 'application/pdf'),
                'description' => 'Bukti baru',
            ])
            ->assertSessionHas('error_portfolio');

        $portfolio->refresh();

        $this->assertSame('verified', $portfolio->status);
        $this->assertSame(9.0, (float) $portfolio->score);
        $this->assertSame('portfolios/lama.pdf', $portfolio->file_path);
        $this->assertSame('Bukti lama', $portfolio->description);
        Storage::disk('local')->assertDirectoryEmpty('portfolios');
    }

    public function test_form_ganti_berkas_disembunyikan_untuk_portofolio_terverifikasi(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        AssessmentPeriod::create(['name' => 'Periode Uji', 'is_active' => true]);
        $kategori = array_key_first(PortfolioCategories::all());

        Portfolio::create([
            'user_id' => $dosen->id,
            'category' => $kategori,
            'file_path' => 'portfolios/lama.pdf',
            'score' => 9,
            'status' => 'verified',
        ]);

        $this->actingAs($dosen)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertDontSee('Ganti File?')
            ->assertSee('Sudah diverifikasi');
    }

    public function test_form_ganti_berkas_tetap_ada_sebelum_diverifikasi(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        AssessmentPeriod::create(['name' => 'Periode Uji', 'is_active' => true]);
        $kategori = array_key_first(PortfolioCategories::all());

        Portfolio::create([
            'user_id' => $dosen->id,
            'category' => $kategori,
            'file_path' => 'portfolios/lama.pdf',
            'score' => 0,
            'status' => 'uploaded',
        ]);

        $this->actingAs($dosen)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('Ganti File?')
            ->assertDontSee('Sudah diverifikasi');
    }

    public function test_unggahan_ulang_tetap_boleh_sebelum_diverifikasi(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('portfolios/lama.pdf', 'isi lama');

        $dosen = User::factory()->create(['role' => 'dosen']);
        $kategori = array_key_first(PortfolioCategories::all());

        $portfolio = Portfolio::create([
            'user_id' => $dosen->id,
            'category' => $kategori,
            'file_path' => 'portfolios/lama.pdf',
            'score' => 0,
            'status' => 'uploaded',
        ]);

        $this->actingAs($dosen)
            ->post(route('portfolio.store'), [
                'category' => $kategori,
                'file' => UploadedFile::fake()->create('baru.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHas('success_portfolio');

        $this->assertNotSame('portfolios/lama.pdf', $portfolio->refresh()->file_path);
        Storage::disk('local')->assertMissing('portfolios/lama.pdf');
    }
}
