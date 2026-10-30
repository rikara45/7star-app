<?php

namespace Tests\Feature;

use App\Models\AssessmentPeriod;
use App\Models\AssessmentRequest;
use App\Models\Portfolio;
use App\Models\User;
use App\Support\PortfolioCategories;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardViewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Kuota dipatok supaya test tidak bergantung pada isi .env mesin lain.
        config([
            'assessment.invitation.max_superior' => 2,
            'assessment.invitation.max_peer' => 5,
        ]);
    }

    public function test_dashboard_menampilkan_semua_bagian_utama(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $period = AssessmentPeriod::forceCreate(['name' => 'Periode Uji', 'is_active' => true]);

        $this->undangan($dosen, $period, 'self', 'Dosen Uji', $dosen->email, false);
        $this->undangan($dosen, $period, 'superior', 'Atasan Uji', 'atasan@example.test', true);
        $this->undangan($dosen, $period, 'peer', 'Sejawat Uji', 'sejawat@example.test', false);

        $this->actingAs($dosen)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('Penilaian Diri Sendiri (Self Assessment)')
            ->assertSee('Undang Penilai')
            ->assertSee('Daftar Penilai')
            ->assertSee('Portofolio (Evidence)')
            ->assertSee('Skor Portofolio')
            ->assertSee('Atasan (1/2)')
            ->assertSee('Sejawat (1/5)')
            ->assertSee('Total: 2')
            ->assertSee('Atasan Uji')
            ->assertSee('Sejawat Uji')
            ->assertSee('Isi Kuesioner Sekarang')
            // Badge status memakai komponen bersama, bukan teks lepas.
            ->assertSee('bg-green-50 text-green-700 border-green-100', false)
            ->assertSee('bg-gray-100 text-gray-600 border-transparent', false)
            ->assertSee('Belum Upload')
            // Syarat belum terpenuhi: tombol proses tidak muncul.
            ->assertSee('Validasi Data Belum Terpenuhi')
            ->assertSee('Minimal 3 Sejawat (Saat ini:', false)
            ->assertDontSee('Proses Hasil & Analisis AI');
    }

    public function test_dashboard_menampilkan_tombol_proses_saat_syarat_terpenuhi(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $period = AssessmentPeriod::forceCreate(['name' => 'Periode Uji', 'is_active' => true]);

        $this->undangan($dosen, $period, 'self', 'Dosen Uji', $dosen->email, true);
        $this->undangan($dosen, $period, 'superior', 'Atasan Uji', 'atasan@example.test', true);

        foreach (['Sejawat Satu', 'Sejawat Dua', 'Sejawat Tiga'] as $i => $nama) {
            $this->undangan($dosen, $period, 'peer', $nama, 'sejawat'.$i.'@example.test', true);
        }

        $categories = array_keys(PortfolioCategories::all());

        foreach (array_slice($categories, 0, 3) as $kategori) {
            Portfolio::create([
                'user_id' => $dosen->id,
                'category' => $kategori,
                'file_path' => 'portfolios/'.$kategori.'.pdf',
                'status' => 'verified',
                'score' => 4,
            ]);
        }

        $this->actingAs($dosen)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('Proses Hasil & Analisis AI')
            ->assertSee('Verified (4)')
            ->assertSee('Ada '.(count($categories) - 3).' kategori kosong', false)
            ->assertDontSee('Validasi Data Belum Terpenuhi');
    }

    public function test_dashboard_penilaian_hanya_untuk_dosen(): void
    {
        foreach (['admin', 'jurusan'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->get(route('dashboard.index'))
                ->assertForbidden();
        }

        $dosen = User::factory()->create(['role' => 'dosen']);
        AssessmentPeriod::forceCreate(['name' => 'Periode Uji', 'is_active' => true]);

        $this->actingAs($dosen)
            ->get(route('dashboard.index'))
            ->assertOk();
    }

    public function test_dashboard_tanpa_periode_aktif_mengembalikan_200(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);

        $this->actingAs($dosen)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('Periode penilaian belum aktif')
            ->assertDontSee('Undang Penilai');
    }

    public function test_dashboard_mengabaikan_data_periode_yang_tidak_aktif(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $lama = AssessmentPeriod::forceCreate(['name' => 'Periode Lama', 'is_active' => false]);

        $this->undangan($dosen, $lama, 'superior', 'Atasan Lama', 'lama@example.test', true);

        $this->actingAs($dosen)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('Periode penilaian belum aktif')
            ->assertDontSee('Atasan Lama')
            ->assertDontSee('Total: 1');
    }

    public function test_komponen_bintang_menandai_bintang_kosong(): void
    {
        $html = Blade::render('<x-star-rating rating="4 Bintang" />');

        $this->assertSame(7, substr_count($html, '★'), 'Harus ada 7 bintang.');
        $this->assertSame(3, substr_count($html, 'text-gray-200'), 'Sisa bintang harus 3.');
    }

    public function test_komponen_badge_memakai_warna_sesuai_tone(): void
    {
        $halus = Blade::render('<x-status-badge tone="green" subtle>Selesai</x-status-badge>');
        $this->assertStringContainsString('bg-green-50', $halus);
        $this->assertStringContainsString('text-green-700', $halus);
        $this->assertStringContainsString('Selesai', $halus);

        $tegas = Blade::render('<x-status-badge tone="blue" size="xs" pulse>Pending</x-status-badge>');
        $this->assertStringContainsString('bg-blue-200', $tegas);
        $this->assertStringContainsString('text-[10px]', $tegas);
        $this->assertStringContainsString('animate-pulse', $tegas);
    }

    private function undangan(
        User $dosen,
        AssessmentPeriod $period,
        string $relationship,
        string $nama,
        string $email,
        bool $selesai,
    ): AssessmentRequest {
        return AssessmentRequest::create([
            'user_id' => $dosen->id,
            'assessment_period_id' => $period->id,
            'assessor_name' => $nama,
            'assessor_email' => $email,
            'relationship' => $relationship,
            'access_token' => Str::random(32),
            'is_completed' => $selesai,
        ]);
    }
}
