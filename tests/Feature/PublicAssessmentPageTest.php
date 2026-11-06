<?php

namespace Tests\Feature;

use App\Models\AssessmentPeriod;
use App\Models\AssessmentRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PublicAssessmentPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Jangan bergantung pada public/hot milik dev server lokal.
        Vite::useHotFile(storage_path('framework/testing/vite.hot'));
    }

    public function test_form_penilaian_memakai_aset_vite_tanpa_cdn(): void
    {
        $response = $this->get(route('assessment.show', $this->token()));

        $response->assertOk()
            ->assertDontSee('cdn.tailwindcss.com')
            ->assertDontSee('cdn.jsdelivr.net');

        $this->assertMemakaiAsetBuild($response);
    }

    public function test_halaman_terima_kasih_memakai_aset_vite_tanpa_cdn(): void
    {
        $response = $this->get(route('assessment.thankyou'));

        $response->assertOk()->assertDontSee('cdn.tailwindcss.com');
        $this->assertMemakaiAsetBuild($response);
    }

    public function test_halaman_selesai_memakai_aset_vite_tanpa_cdn(): void
    {
        $response = $this->get(route('assessment.completed'));

        $response->assertOk()->assertDontSee('cdn.tailwindcss.com');
        $this->assertMemakaiAsetBuild($response);
    }

    private function assertMemakaiAsetBuild(TestResponse $response): void
    {
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);

        $response
            ->assertSee($manifest['resources/css/app.css']['file'], false)
            ->assertSee($manifest['resources/js/app.js']['file'], false);
    }

    private function token(): string
    {
        $user = User::factory()->create(['role' => 'dosen']);
        $period = AssessmentPeriod::create(['name' => 'Periode Uji', 'is_active' => true]);

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
