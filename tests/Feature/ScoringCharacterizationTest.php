<?php

namespace Tests\Feature;

use App\Models\AiAnalysis;
use App\Models\AssessmentPeriod;
use App\Models\AssessmentRequest;
use App\Models\AssessmentResponse;
use App\Models\MasterQuestion;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Mengunci perilaku skor saat ini (70% perilaku + 30% portofolio → 1-7 bintang)
 * supaya refactor berikutnya tidak menggeser angka yang sudah dipakai produksi.
 */
class ScoringCharacterizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            '*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'Narasi uji.']]],
                ]],
            ]),
        ]);
    }

    public function test_skor_akhir_menggabungkan_bobot_70_30_dengan_benar(): void
    {
        $user = $this->dosen();
        $period = $this->activePeriod();

        // rata-rata 4.5 dari skala 1-5 → 4.5/5*100 = 90
        $this->completedAssessment($user, $period, [5, 4]);
        $this->portfolios($user, 93);

        $this->actingAs($user)->post(route('result.generate'))->assertRedirect(route('result.show'));

        $analysis = AiAnalysis::where('user_id', $user->id)->firstOrFail();

        $this->assertEqualsWithDelta(90.0, (float) $analysis->score_behavior, 0.001);
        $this->assertEqualsWithDelta(93.0, (float) $analysis->score_portfolio, 0.001);
        $this->assertEqualsWithDelta(90.9, (float) $analysis->final_score, 0.001);
        $this->assertSame('6 Bintang', $analysis->star_rating);
    }

    #[DataProvider('ambangBintang')]
    public function test_ambang_bintang_sesuai_batas_yang_berlaku(array $scores, int $portfolioSum, float $expectedFinal, string $expectedStars): void
    {
        $user = $this->dosen();
        $period = $this->activePeriod();

        $this->completedAssessment($user, $period, $scores);
        $this->portfolios($user, $portfolioSum);

        $this->actingAs($user)->post(route('result.generate'))->assertRedirect(route('result.show'));

        $analysis = AiAnalysis::where('user_id', $user->id)->firstOrFail();

        $this->assertEqualsWithDelta($expectedFinal, (float) $analysis->final_score, 0.001);
        $this->assertSame($expectedStars, $analysis->star_rating);
    }

    public static function ambangBintang(): array
    {
        return [
            '100.0 → 7 bintang' => [[5], 100, 100.0, '7 Bintang'],
            '91.0 (batas bawah 7) → 7 bintang' => [[5], 70, 91.0, '7 Bintang'],
            '90.9 → 6 bintang' => [[5, 4], 93, 90.9, '6 Bintang'],
            '86.0 (batas bawah 6) → 6 bintang' => [[4], 100, 86.0, '6 Bintang'],
            '85.7 → 5 bintang' => [[4], 99, 85.7, '5 Bintang'],
            '80.0 (batas bawah 5) → 5 bintang' => [[4], 80, 80.0, '5 Bintang'],
            '79.7 → 4 bintang' => [[4], 79, 79.7, '4 Bintang'],
            '72.0 (batas bawah 4) → 4 bintang' => [[3], 100, 72.0, '4 Bintang'],
            '71.7 → 3 bintang' => [[3], 99, 71.7, '3 Bintang'],
            '65.0 (batas bawah 3) → 3 bintang' => [[2, 3], 100, 65.0, '3 Bintang'],
            '64.7 → 2 bintang' => [[2, 3], 99, 64.7, '2 Bintang'],
            '55.0 (batas bawah 2) → 2 bintang' => [[4, 3], 20, 55.0, '2 Bintang'],
            '54.7 → 1 bintang' => [[4, 3], 19, 54.7, '1 Bintang'],
        ];
    }

    public function test_skor_portofolio_dibatasi_seratus_meski_total_melebihi(): void
    {
        $user = $this->dosen();
        $period = $this->activePeriod();

        $this->completedAssessment($user, $period, [5]);
        $this->portfolios($user, 150);

        $this->actingAs($user)->post(route('result.generate'))->assertRedirect(route('result.show'));

        $analysis = AiAnalysis::where('user_id', $user->id)->firstOrFail();

        $this->assertEqualsWithDelta(100.0, (float) $analysis->score_portfolio, 0.001);
        $this->assertEqualsWithDelta(100.0, (float) $analysis->final_score, 0.001);
    }

    public function test_halaman_hasil_mengalihkan_dengan_pesan_saat_tidak_ada_periode_aktif(): void
    {
        $user = $this->dosen();
        $period = $this->activePeriod();

        $this->completedAssessment($user, $period, [5]);
        AiAnalysis::create([
            'user_id' => $user->id,
            'assessment_period_id' => $period->id,
            'score_behavior' => 100,
            'score_portfolio' => 100,
            'final_score' => 100,
            'star_rating' => '7 Bintang',
            'ai_narrative' => 'Narasi lama.',
        ]);

        $period->forceFill(['is_active' => false])->save();

        $this->actingAs($user)
            ->get(route('result.show'))
            ->assertRedirect(route('dashboard.index'))
            ->assertSessionHas('error');
    }

    public function test_unduh_pdf_mengalihkan_dengan_pesan_saat_tidak_ada_periode_aktif(): void
    {
        $user = $this->dosen();
        $period = $this->activePeriod();

        $this->completedAssessment($user, $period, [5]);
        AiAnalysis::create([
            'user_id' => $user->id,
            'assessment_period_id' => $period->id,
            'score_behavior' => 100,
            'score_portfolio' => 100,
            'final_score' => 100,
            'star_rating' => '7 Bintang',
            'ai_narrative' => 'Narasi lama.',
        ]);

        $period->forceFill(['is_active' => false])->save();

        $this->actingAs($user)
            ->get(route('result.pdf'))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_narasi_ai_memakai_api_key_dari_config_bukan_env(): void
    {
        config(['gemini.api_key' => 'kunci-dari-config']);

        $user = $this->dosen();
        $period = $this->activePeriod();

        $this->completedAssessment($user, $period, [5]);

        $this->actingAs($user)->post(route('result.generate'))->assertRedirect(route('result.show'));

        $sentUrl = null;
        Http::assertSent(function ($request) use (&$sentUrl) {
            $sentUrl = $request->url();

            return true;
        });

        $this->assertTrue(
            str_contains((string) $sentUrl, 'key=kunci-dari-config'),
            'Panggilan Gemini harus memakai config(gemini.api_key) agar tetap jalan setelah config:cache.'
        );
    }

    private function dosen(): User
    {
        return User::factory()->create(['role' => 'dosen']);
    }

    private function activePeriod(): AssessmentPeriod
    {
        return AssessmentPeriod::forceCreate(['name' => 'Periode Uji', 'is_active' => true]);
    }

    private function completedAssessment(User $user, AssessmentPeriod $period, array $scores): void
    {
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
    }

    private function portfolios(User $user, int $total): void
    {
        $remaining = $total;

        while ($remaining > 0) {
            $score = min(10, $remaining);
            Portfolio::create([
                'user_id' => $user->id,
                'category' => 'Pendidikan & Sertifikasi',
                'file_path' => 'portofolio/uji.pdf',
                'description' => 'Bukti uji',
                'score' => $score,
                'status' => 'verified',
            ]);
            $remaining -= $score;
        }
    }
}
