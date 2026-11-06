<?php

namespace Tests\Feature;

use App\Models\AssessmentPeriod;
use App\Models\AssessmentRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Link penilai atasan/sejawat hanya boleh sampai ke email penilai. Bila peserta
 * bisa menyalinnya, ia dapat mengisi sendiri form 70% penilaiannya.
 */
class AssessorLinkVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_tidak_memuat_link_penilai_atasan_atau_sejawat(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $period = AssessmentPeriod::create(['name' => 'Periode Uji', 'is_active' => true]);

        $peer = $this->requestUntuk($dosen, $period, 'peer', 'Sejawat Uji');
        $superior = $this->requestUntuk($dosen, $period, 'superior', 'Atasan Uji');

        $this->actingAs($dosen)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('Sejawat Uji')
            ->assertSee('Atasan Uji')
            ->assertDontSee(route('assessment.show', $peer->access_token))
            ->assertDontSee(route('assessment.show', $superior->access_token))
            ->assertSee('Link dikirim ke email penilai');
    }

    public function test_link_penilaian_diri_tetap_tersedia(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $period = AssessmentPeriod::create(['name' => 'Periode Uji', 'is_active' => true]);
        $self = $this->requestUntuk($dosen, $period, 'self', $dosen->name);

        $this->actingAs($dosen)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee(route('assessment.show', $self->access_token));
    }

    private function requestUntuk(User $user, AssessmentPeriod $period, string $relationship, string $nama): AssessmentRequest
    {
        return AssessmentRequest::create([
            'user_id' => $user->id,
            'assessment_period_id' => $period->id,
            'assessor_name' => $nama,
            'assessor_email' => Str::slug($nama).'@uji.local',
            'relationship' => $relationship,
            'access_token' => Str::random(32),
        ]);
    }
}
