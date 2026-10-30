<?php

namespace Tests\Feature;

use App\Models\AiAnalysis;
use App\Models\AssessmentPeriod;
use App\Models\AssessmentRequest;
use App\Models\AssessmentResponse;
use App\Models\MasterQuestion;
use App\Models\UniversitySetting;
use App\Models\User;
use App\Services\AssessmentResultService;
use App\Services\AssessmentScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class AssessmentResultTest extends TestCase
{
    use RefreshDatabase;

    public function test_data_hasil_memakai_rincian_dimensi_dari_score_service(): void
    {
        [$user, $period] = $this->dosenWithAnalysis();

        $expected = app(AssessmentScoreService::class)->dimensionScores($user->id, $period);
        $data = app(AssessmentResultService::class)->viewDataFor($user, $period);

        $this->assertNotNull($data);
        $this->assertSame($expected, $data['dimensionDetails']);
    }

    public function test_data_hasil_null_bila_belum_ada_analisis(): void
    {
        [$user, $period] = $this->dosenWithAnalysis(analysis: false);

        $this->assertNull(app(AssessmentResultService::class)->viewDataFor($user, $period));
    }

    public function test_analisis_lintas_periode_tetap_memakai_yang_terbaru(): void
    {
        [$user, $period] = $this->dosenWithAnalysis();

        $lain = AssessmentPeriod::forceCreate(['name' => 'Periode Lain', 'is_active' => false]);
        AiAnalysis::forceCreate([
            'user_id' => $user->id,
            'assessment_period_id' => $lain->id,
            'score_behavior' => 10,
            'score_portfolio' => 10,
            'final_score' => 10,
            'star_rating' => '1 Bintang',
            'ai_narrative' => 'Narasi periode lain.',
            'created_at' => now()->addMinute(),
            'updated_at' => now()->addMinute(),
        ]);

        $data = app(AssessmentResultService::class)->viewDataFor($user, $period);

        $this->assertSame('Narasi periode lain.', $data['analysis']->ai_narrative);
    }

    public function test_proses_hasil_menyimpan_analisis_lewat_service(): void
    {
        Http::fake(['*' => Http::response($this->geminiResponse('Narasi uji.'))]);

        [$user, $period] = $this->dosenWithScores([5, 4]);

        $this->actingAs($user)->post(route('result.generate'))->assertRedirect(route('result.show'));

        $analysis = AiAnalysis::firstOrFail();

        $this->assertSame($user->id, $analysis->user_id);
        $this->assertSame($period->id, $analysis->assessment_period_id);
        $this->assertEqualsWithDelta(90.0, (float) $analysis->score_behavior, 0.001);
        $this->assertSame('Narasi uji.', $analysis->ai_narrative);
    }

    public function test_proses_hasil_tidak_memanggil_ai_bila_skor_tidak_berubah(): void
    {
        Http::fake();
        [$user] = $this->dosenWithAnalysis();

        $this->actingAs($user)->post(route('result.generate'))->assertRedirect(route('result.show'));

        Http::assertNothingSent();
        $this->assertSame(1, AiAnalysis::where('user_id', $user->id)->count());
    }

    public function test_proses_hasil_tetap_menyimpan_skor_saat_ai_gagal(): void
    {
        Http::fake(['*' => Http::response('', 500)]);

        [$user] = $this->dosenWithScores([5]);

        $this->actingAs($user)->post(route('result.generate'))->assertRedirect(route('result.show'));

        $analysis = AiAnalysis::firstOrFail();

        $this->assertEqualsWithDelta(100.0, (float) $analysis->score_behavior, 0.001);
        $this->assertStringContainsString('belum tersedia', $analysis->ai_narrative);
    }

    public function test_halaman_hasil_menampilkan_rincian_dimensi(): void
    {
        [$user] = $this->dosenWithAnalysis();

        $this->actingAs($user)
            ->get(route('result.show'))
            ->assertOk()
            ->assertSee('Profesionalisme')
            ->assertSee('4.50');
    }

    public function test_halaman_hasil_mengalihkan_bila_belum_ada_analisis(): void
    {
        [$user] = $this->dosenWithAnalysis(analysis: false);

        $this->actingAs($user)
            ->get(route('result.show'))
            ->assertRedirect(route('dashboard.index'))
            ->assertSessionHas('error', 'Belum ada hasil. Silakan proses dulu.');
    }

    public function test_pdf_dosen_terunduh_dengan_data_yang_sama(): void
    {
        [$user] = $this->dosenWithAnalysis();

        UniversitySetting::forceCreate([
            'uni_name' => 'Universitas Uji',
            'uni_address' => 'Jl. Uji',
            'uni_website' => 'https://uji.test',
        ]);

        $response = $this->actingAs($user)->get(route('result.pdf'));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
    }

    private function dosenWithAnalysis(bool $analysis = true): array
    {
        [$user, $period] = $this->dosenWithScores([5, 4]);

        if ($analysis) {
            AiAnalysis::forceCreate([
                'user_id' => $user->id,
                'assessment_period_id' => $period->id,
                'score_behavior' => 90,
                'score_portfolio' => 0,
                'final_score' => 63,
                'star_rating' => '2 Bintang',
                'ai_narrative' => 'Narasi tersimpan.',
            ]);
        }

        return [$user, $period];
    }

    private function dosenWithScores(array $scores): array
    {
        $user = User::factory()->create(['role' => 'dosen']);
        $period = $this->period();

        $request = AssessmentRequest::forceCreate([
            'user_id' => $user->id,
            'assessment_period_id' => $period->id,
            'assessor_name' => 'Atasan Uji',
            'assessor_email' => 'atasan@example.test',
            'relationship' => 'superior',
            'access_token' => Str::random(40),
            'is_completed' => true,
        ]);

        foreach ($scores as $score) {
            $question = MasterQuestion::forceCreate([
                'dimension' => 'Profesionalisme',
                'statement_self' => 'Pernyataan self',
                'statement_other' => 'Pernyataan other',
            ]);

            AssessmentResponse::forceCreate([
                'assessment_request_id' => $request->id,
                'master_question_id' => $question->id,
                'score' => $score,
            ]);
        }

        return [$user, $period];
    }

    private function period(): AssessmentPeriod
    {
        return AssessmentPeriod::forceCreate(['name' => 'Periode Uji', 'is_active' => true]);
    }

    private function geminiResponse(string $text): array
    {
        return ['candidates' => [['content' => ['parts' => [['text' => $text]]]]]];
    }
}
