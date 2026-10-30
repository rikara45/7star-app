<?php

namespace Tests\Feature;

use App\Models\AssessmentPeriod;
use App\Models\AssessmentRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardViewTest extends TestCase
{
    use RefreshDatabase;

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
            ->assertSee('Selesai')
            ->assertSee('Pending')
            ->assertSee('Belum Upload')
            ->assertSee('Isi Kuesioner Sekarang');
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

    public function test_dashboard_dengan_periode_tidak_aktif_mengembalikan_200(): void
    {
        AssessmentPeriod::forceCreate(['name' => 'Periode Lama', 'is_active' => false]);

        $dosen = User::factory()->create(['role' => 'dosen']);

        $this->actingAs($dosen)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('Periode penilaian belum aktif');
    }

    public function test_komponen_bintang_menandai_bintang_kosong(): void
    {
        $html = Blade::render('<x-star-rating rating="4 Bintang" />');

        $this->assertSame(4, substr_count($html, '<span class="">★</span>'), 'Bintang terisi harus 4.');
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
