<?php

namespace Tests\Feature;

use App\Models\AssessmentPeriod;
use App\Models\Portfolio;
use App\Models\User;
use App\Support\PortfolioCategories;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortfolioUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_kategori_di_luar_daftar_ditolak(): void
    {
        Storage::fake('local');

        $dosen = User::factory()->create(['role' => 'dosen']);

        $this->actingAs($dosen)
            ->post(route('portfolio.store'), [
                'category' => 'Kategori Karangan',
                'file' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('category');

        $this->assertDatabaseCount('portfolios', 0);
        Storage::disk('local')->assertDirectoryEmpty('portfolios');
    }

    public function test_kategori_sah_diterima(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $dosen = User::factory()->create(['role' => 'dosen']);
        $category = array_key_first(PortfolioCategories::all());

        $this->actingAs($dosen)
            ->post(route('portfolio.store'), [
                'category' => $category,
                'file' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
                'description' => 'Ijazah S3',
            ])
            ->assertRedirect()
            ->assertSessionHas('success_portfolio');

        $this->assertDatabaseHas('portfolios', [
            'user_id' => $dosen->id,
            'category' => $category,
            'description' => 'Ijazah S3',
            'status' => 'uploaded',
            'score' => 0,
        ]);
    }

    public function test_unggahan_baru_tersimpan_di_disk_privat(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $dosen = User::factory()->create(['role' => 'dosen']);
        $category = array_key_first(PortfolioCategories::all());

        $this->actingAs($dosen)
            ->post(route('portfolio.store'), [
                'category' => $category,
                'file' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect();

        $portfolio = $dosen->portfolios()->firstOrFail();

        $this->assertStringStartsWith('portfolios/', $portfolio->file_path);
        Storage::disk('local')->assertExists($portfolio->file_path);
        Storage::disk('public')->assertMissing($portfolio->file_path);
    }

    public function test_pesan_validasi_tampil_di_dashboard(): void
    {
        Storage::fake('local');

        $dosen = User::factory()->create(['role' => 'dosen']);
        AssessmentPeriod::forceCreate(['name' => 'Periode Uji', 'is_active' => true]);

        $this->actingAs($dosen)
            ->from(route('dashboard.index'))
            ->post(route('portfolio.store'), [
                'category' => array_key_first(PortfolioCategories::all()),
                'file' => UploadedFile::fake()->create('bukti.exe', 10, 'application/octet-stream'),
            ])
            ->assertRedirect(route('dashboard.index'))
            ->assertSessionHasErrors('file');

        $this->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('Berkas harus berupa PDF, JPG, JPEG, atau PNG.');
    }

    public function test_mengganti_berkas_mempertahankan_keterangan(): void
    {
        Storage::fake('local');

        $dosen = User::factory()->create(['role' => 'dosen']);
        $category = array_key_first(PortfolioCategories::all());

        $this->actingAs($dosen)->post(route('portfolio.store'), [
            'category' => $category,
            'file' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
            'description' => 'Ijazah S3',
        ]);

        $this->actingAs($dosen)->post(route('portfolio.store'), [
            'category' => $category,
            'file' => UploadedFile::fake()->create('bukti-baru.pdf', 100, 'application/pdf'),
        ]);

        $portfolio = $dosen->portfolios()->firstOrFail();

        $this->assertSame('Ijazah S3', $portfolio->description);
        $this->assertDatabaseCount('portfolios', 1);
    }

    public function test_peringatan_reset_nilai_tampil_saat_berkas_sudah_ada(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        AssessmentPeriod::forceCreate(['name' => 'Periode Uji', 'is_active' => true]);

        Portfolio::create([
            'user_id' => $dosen->id,
            'category' => 'Kinerja Akademik',
            'file_path' => 'portfolios/lama.pdf',
            'status' => 'verified',
            'score' => 8,
        ]);

        $this->actingAs($dosen)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('Mengganti berkas akan mengosongkan nilai verifikasi');
    }

    public function test_kegagalan_menulis_berkas_tidak_menyimpan_baris(): void
    {
        $blocker = storage_path('framework/testing/disk-blocker');

        if (! is_dir(dirname($blocker))) {
            mkdir(dirname($blocker), 0777, true);
        }

        if (file_exists($blocker)) {
            unlink($blocker);
        }

        file_put_contents($blocker, 'x');

        try {
            Config::set('filesystems.disks.local.root', $blocker.DIRECTORY_SEPARATOR.'sub');
            Storage::forgetDisk('local');

            $dosen = User::factory()->create(['role' => 'dosen']);

            $this->actingAs($dosen)
                ->post(route('portfolio.store'), [
                    'category' => array_key_first(PortfolioCategories::all()),
                    'file' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
                ])
                ->assertSessionHas('error');

            $this->assertDatabaseCount('portfolios', 0);
        } finally {
            @unlink($blocker);
        }
    }
}
