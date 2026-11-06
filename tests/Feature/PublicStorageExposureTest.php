<?php

namespace Tests\Feature;

use App\Models\AiAnalysis;
use App\Models\AssessmentPeriod;
use App\Models\UniversitySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Docroot `public/` tidak boleh memuat salinan berkas unggahan. Portofolio
 * disajikan lewat route terotorisasi, bukan sebagai file statis.
 */
class PublicStorageExposureTest extends TestCase
{
    use RefreshDatabase;

    public function test_storage_di_docroot_tidak_berupa_direktori_salinan(): void
    {
        $path = public_path('storage');

        if (! file_exists($path)) {
            $this->assertTrue(true);

            return;
        }

        $this->assertTrue(
            is_link($path),
            'public/storage harus symlink (storage:link) atau tidak ada, bukan direktori salinan.'
        );
    }

    public function test_tidak_ada_berkas_portofolio_di_docroot(): void
    {
        $files = glob(public_path('storage/portfolios/*')) ?: [];

        $this->assertSame([], $files, 'Berkas portofolio ditemukan di docroot: '.implode(', ', $files));
    }

    public function test_tidak_ada_portofolio_di_disk_publik(): void
    {
        $files = glob(storage_path('app/public/portfolios/*')) ?: [];

        $this->assertSame([], $files, 'Portofolio di disk publik ikut terekspos lewat symlink: '.implode(', ', $files));
    }

    public function test_htaccess_menolak_akses_langsung_ke_portofolio(): void
    {
        $htaccess = file_get_contents(public_path('.htaccess'));

        $this->assertStringContainsString('/storage/portfolios', $htaccess);
    }

    public function test_logo_pada_pdf_memakai_path_disk_publik(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('settings/logo.png', 'gambar');

        $user = User::factory()->create(['role' => 'dosen']);
        $period = AssessmentPeriod::create(['name' => 'Periode Uji', 'is_active' => true]);
        $analysis = AiAnalysis::create([
            'user_id' => $user->id,
            'assessment_period_id' => $period->id,
            'score_behavior' => 80,
            'score_portfolio' => 20,
            'final_score' => 62,
            'star_rating' => '2 Bintang',
            'ai_narrative' => 'Narasi',
        ]);
        $setting = UniversitySetting::create([
            'uni_name' => 'Universitas Uji',
            'uni_logo_path' => 'settings/logo.png',
        ]);

        $html = view('pdf.report', [
            'user' => $user,
            'analysis' => $analysis,
            'setting' => $setting,
            'dimensionDetails' => [],
        ])->render();

        $this->assertStringContainsString(Storage::disk('public')->path('settings/logo.png'), $html);
        $this->assertStringNotContainsString(public_path('storage/settings/logo.png'), $html);
    }
}
