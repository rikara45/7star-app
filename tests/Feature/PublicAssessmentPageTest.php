<?php

namespace Tests\Feature;

use App\Models\AssessmentPeriod;
use App\Models\AssessmentRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicAssessmentPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_form_penilaian_memakai_aset_vite_tanpa_cdn(): void
    {
        $this->get(route('assessment.show', $this->token()))
            ->assertOk()
            ->assertDontSee('cdn.tailwindcss.com')
            ->assertDontSee('cdn.jsdelivr.net')
            ->assertSee('build/assets', false);
    }

    public function test_halaman_terima_kasih_memakai_aset_vite_tanpa_cdn(): void
    {
        $this->get(route('assessment.thankyou'))
            ->assertOk()
            ->assertDontSee('cdn.tailwindcss.com')
            ->assertSee('build/assets', false);
    }

    public function test_halaman_selesai_memakai_aset_vite_tanpa_cdn(): void
    {
        $this->get(route('assessment.completed'))
            ->assertOk()
            ->assertDontSee('cdn.tailwindcss.com')
            ->assertSee('build/assets', false);
    }

    private function token(): string
    {
        $user = User::factory()->create(['role' => 'dosen']);
        $period = AssessmentPeriod::forceCreate(['name' => 'Periode Uji', 'is_active' => true]);

        return AssessmentRequest::create([
            'user_id' => $user->id,
            'assessment_period_id' => $period->id,
            'assessor_name' => 'Penilai',
            'assessor_email' => 'penilai@uji.local',
            'relationship' => 'peer',
            'access_token' => Str::random(32),
        ])->access_token;
    }
}
