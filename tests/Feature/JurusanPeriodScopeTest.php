<?php

namespace Tests\Feature;

use App\Models\AssessmentPeriod;
use App\Models\Portfolio;
use App\Models\User;
use App\Support\PortfolioCategories;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Layar jurusan bekerja pada periode aktif. Bukti periode lalu tetap tersimpan,
 * tetapi tidak muncul di daftar verifikasi maupun statistik periode berjalan.
 */
class JurusanPeriodScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_detail_verifikasi_hanya_menampilkan_bukti_periode_aktif(): void
    {
        $jurusan = User::factory()->create(['role' => 'jurusan']);
        $dosen = User::factory()->create(['role' => 'dosen']);
        $periodeA = AssessmentPeriod::create(['name' => 'Periode A', 'is_active' => false]);
        $periodeB = AssessmentPeriod::create(['name' => 'Periode B', 'is_active' => true]);

        $kategori = array_keys(PortfolioCategories::all());

        $this->bukti($dosen, $periodeA, 'Bukti A', 'verified', 9, $kategori[0]);
        $this->bukti($dosen, $periodeB, 'Bukti B', 'uploaded', 0, $kategori[1]);

        $this->actingAs($jurusan)
            ->get(route('jurusan.verifikasi.show', $dosen->id))
            ->assertOk()
            ->assertSee('Bukti B')
            ->assertDontSee('Bukti A');
    }

    public function test_statistik_jurusan_menghitung_periode_aktif_saja(): void
    {
        $jurusan = User::factory()->create(['role' => 'jurusan']);
        $dosen = User::factory()->create(['role' => 'dosen']);
        $periodeA = AssessmentPeriod::create(['name' => 'Periode A', 'is_active' => false]);
        $periodeB = AssessmentPeriod::create(['name' => 'Periode B', 'is_active' => true]);

        $this->bukti($dosen, $periodeA, 'Bukti A', 'verified', 9);
        $this->bukti($dosen, $periodeB, 'Bukti B', 'uploaded', 0);

        $this->actingAs($jurusan)
            ->get(route('jurusan.dashboard'))
            ->assertOk()
            ->assertViewHas('totalPendingItems', 1)
            ->assertViewHas('totalVerifiedItems', 0);
    }

    public function test_filter_pending_mengabaikan_bukti_periode_lama(): void
    {
        $jurusan = User::factory()->create(['role' => 'jurusan']);
        $dosenLama = User::factory()->create(['role' => 'dosen', 'name' => 'Dosen Bukti Lama']);
        $periodeA = AssessmentPeriod::create(['name' => 'Periode A', 'is_active' => false]);
        $periodeB = AssessmentPeriod::create(['name' => 'Periode B', 'is_active' => true]);

        $this->bukti($dosenLama, $periodeA, 'Bukti A', 'uploaded', 0);
        $this->bukti($dosenLama, $periodeB, 'Bukti B', 'verified', 8);

        $this->actingAs($jurusan)
            ->get(route('jurusan.verifikasi.index', ['status' => 'pending']))
            ->assertOk()
            ->assertDontSee('Dosen Bukti Lama');
    }

    public function test_tanpa_periode_aktif_semua_bukti_tetap_tampil(): void
    {
        $jurusan = User::factory()->create(['role' => 'jurusan']);
        $dosen = User::factory()->create(['role' => 'dosen']);
        $periode = AssessmentPeriod::create(['name' => 'Periode A', 'is_active' => false]);

        $this->bukti($dosen, $periode, 'Bukti Tanpa Periode Aktif', 'uploaded', 0);

        $this->actingAs($jurusan)
            ->get(route('jurusan.verifikasi.show', $dosen->id))
            ->assertOk()
            ->assertSee('Bukti Tanpa Periode Aktif');
    }

    private function bukti(User $dosen, AssessmentPeriod $periode, string $keterangan, string $status, int $skor, ?string $kategori = null): void
    {
        Portfolio::create([
            'user_id' => $dosen->id,
            'assessment_period_id' => $periode->id,
            'category' => $kategori ?? array_key_first(PortfolioCategories::all()),
            'file_path' => 'portfolios/'.$periode->id.'-'.md5($keterangan).'.pdf',
            'description' => $keterangan,
            'status' => $status,
            'score' => $skor,
        ]);
    }
}
