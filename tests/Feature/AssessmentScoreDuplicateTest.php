<?php

namespace Tests\Feature;

use App\Models\AssessmentPeriod;
use App\Models\AssessmentRequest;
use App\Models\AssessmentResponse;
use App\Models\MasterQuestion;
use App\Models\User;
use App\Services\AssessmentScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Data lama dari create() bisa menyimpan dua baris untuk pertanyaan yang sama.
 * Skor harus memakai jawaban terbaru, bukan menghitung keduanya sebagai bobot ganda.
 */
class AssessmentScoreDuplicateTest extends TestCase
{
    use RefreshDatabase;

    public function test_baris_duplikat_dihitung_sekali_dengan_skor_terbaru(): void
    {
        $user = User::factory()->create(['role' => 'dosen']);
        $period = AssessmentPeriod::forceCreate(['name' => 'Periode Uji', 'is_active' => true]);

        $request = AssessmentRequest::forceCreate([
            'user_id' => $user->id,
            'assessment_period_id' => $period->id,
            'assessor_name' => 'Atasan Uji',
            'assessor_email' => 'atasan@example.test',
            'relationship' => 'superior',
            'access_token' => Str::random(32),
            'is_completed' => true,
        ]);

        $question = MasterQuestion::forceCreate([
            'dimension' => 'Profesionalisme',
            'statement_self' => 'Pernyataan self',
            'statement_other' => 'Pernyataan other',
        ]);

        foreach ([5, 1] as $score) {
            AssessmentResponse::forceCreate([
                'assessment_request_id' => $request->id,
                'master_question_id' => $question->id,
                'score' => $score,
            ]);
        }

        $scores = app(AssessmentScoreService::class)->dimensionScores($user->id, $period);

        $this->assertSame(['Profesionalisme' => 1.0], $scores);
    }
}
