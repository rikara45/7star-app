<?php

namespace Tests\Feature;

use App\Models\AiAnalysis;
use App\Models\AssessmentPeriod;
use App\Models\AssessmentRequest;
use App\Models\MasterQuestion;
use App\Models\User;
use App\Support\DuplicateRowCleaner;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class UniqueIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_jawaban_ganda_untuk_pertanyaan_yang_sama_ditolak(): void
    {
        [$request, $question] = $this->permintaanDanPertanyaan();

        DB::table('assessment_responses')->insert([
            'assessment_request_id' => $request->id,
            'master_question_id' => $question->id,
            'score' => 5,
        ]);

        $this->expectException(QueryException::class);

        DB::table('assessment_responses')->insert([
            'assessment_request_id' => $request->id,
            'master_question_id' => $question->id,
            'score' => 1,
        ]);
    }

    public function test_analisis_ganda_untuk_periode_yang_sama_ditolak(): void
    {
        $user = User::factory()->create(['role' => 'dosen']);
        $period = AssessmentPeriod::create(['name' => 'Periode Uji', 'is_active' => true]);

        AiAnalysis::create($this->analisis($user, $period));

        $this->expectException(QueryException::class);

        AiAnalysis::create($this->analisis($user, $period));
    }

    public function test_cleaner_menyisakan_jawaban_terbaru(): void
    {
        [$request, $question] = $this->permintaanDanPertanyaan();

        $this->dropUnique('assessment_responses', 'assessment_responses_request_question_unique');

        foreach ([5, 1, 3] as $score) {
            DB::table('assessment_responses')->insert([
                'assessment_request_id' => $request->id,
                'master_question_id' => $question->id,
                'score' => $score,
            ]);
        }

        $this->assertSame(2, DuplicateRowCleaner::responses());
        $this->assertSame(1, DB::table('assessment_responses')->count());
        $this->assertSame(3, (int) DB::table('assessment_responses')->value('score'));
    }

    /**
     * @return array{0: AssessmentRequest, 1: MasterQuestion}
     */
    private function permintaanDanPertanyaan(): array
    {
        $user = User::factory()->create(['role' => 'dosen']);
        $period = AssessmentPeriod::create(['name' => 'Periode Uji', 'is_active' => true]);

        $request = AssessmentRequest::create([
            'user_id' => $user->id,
            'assessment_period_id' => $period->id,
            'assessor_name' => 'Atasan Uji',
            'assessor_email' => 'atasan@uji.local',
            'relationship' => 'superior',
            'access_token' => Str::random(32),
            'is_completed' => true,
        ]);

        $question = MasterQuestion::forceCreate([
            'dimension' => 'Profesionalisme',
            'statement_self' => 'Pernyataan self',
            'statement_other' => 'Pernyataan other',
        ]);

        return [$request, $question];
    }

    /**
     * @return array<string, mixed>
     */
    private function analisis(User $user, AssessmentPeriod $period): array
    {
        return [
            'user_id' => $user->id,
            'assessment_period_id' => $period->id,
            'score_behavior' => 80,
            'score_portfolio' => 20,
            'final_score' => 62,
            'star_rating' => '2 Bintang',
            'ai_narrative' => 'Narasi',
        ];
    }

    private function dropUnique(string $table, string $index): void
    {
        Schema::table($table, fn ($blueprint) => $blueprint->dropUnique($index));
    }
}
