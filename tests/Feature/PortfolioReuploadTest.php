<?php

namespace Tests\Feature;

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
            ->assertSessionHas('error');

        $portfolio->refresh();

        $this->assertSame('verified', $portfolio->status);
        $this->assertSame(9.0, (float) $portfolio->score);
        $this->assertSame('portfolios/lama.pdf', $portfolio->file_path);
        $this->assertSame('Bukti lama', $portfolio->description);
        Storage::disk('local')->assertDirectoryEmpty('portfolios');
    }

    public function test_unggahan_ulang_tetap_boleh_sebelum_diverifikasi(): void
    {
        Storage::fake('local');

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
    }
}
