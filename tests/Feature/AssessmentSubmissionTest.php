<?php

namespace Tests\Feature;

use App\Models\AssessmentPeriod;
use App\Models\AssessmentRequest;
use App\Models\MasterQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AssessmentSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_kiriman_lengkap_mengisi_completed_at(): void
    {
        [$request, $questions] = $this->tokenWithQuestions(3);

        $this->post(route('assessment.store', $request->access_token), [
            'answers' => $questions->pluck('id')->mapWithKeys(fn ($id) => [$id => 4])->all(),
        ])->assertRedirect(route('assessment.thankyou'));

        $fresh = $request->fresh();

        $this->assertTrue($fresh->is_completed);
        $this->assertNotNull($fresh->completed_at);
    }

    public function test_id_pertanyaan_palsu_ditolak_422(): void
    {
        [$request] = $this->tokenWithQuestions(2);

        $this->post(route('assessment.store', $request->access_token), [
            'answers' => [999999 => 4],
        ])->assertSessionHasErrors('answers');

        $this->assertDatabaseCount('assessment_responses', 0);
    }

    public function test_jawaban_kosong_ditolak_422(): void
    {
        [$request] = $this->tokenWithQuestions(2);

        $this->post(route('assessment.store', $request->access_token), [
            'answers' => [],
        ])->assertSessionHasErrors('answers');

        $this->assertDatabaseCount('assessment_responses', 0);
    }

    public function test_skor_di_luar_1_sampai_5_ditolak_422(): void
    {
        [$request, $questions] = $this->tokenWithQuestions(2);

        $this->post(route('assessment.store', $request->access_token), [
            'answers' => [$questions[0]->id => 0, $questions[1]->id => 6],
        ])->assertSessionHasErrors([
            'answers.'.$questions[0]->id,
            'answers.'.$questions[1]->id,
        ]);

        $this->assertDatabaseCount('assessment_responses', 0);
    }

    public function test_kiriman_parsial_tersimpan_tanpa_menandai_selesai(): void
    {
        [$request, $questions] = $this->tokenWithQuestions(3);

        $this->post(route('assessment.store', $request->access_token), [
            'answers' => [$questions[0]->id => 5, $questions[1]->id => 3],
        ])->assertRedirect(route('assessment.show', $request->access_token))
            ->assertSessionHas('status', 'Jawaban tersimpan (2 dari 3). Masih ada 1 pertanyaan.');

        $fresh = $request->fresh();

        $this->assertFalse($fresh->is_completed);
        $this->assertNull($fresh->completed_at);
        $this->assertSame(2, $fresh->responses()->count());
    }

    public function test_melanjutkan_kiriman_parsial_tidak_menggandakan_jawaban(): void
    {
        [$request, $questions] = $this->tokenWithQuestions(3);

        $this->post(route('assessment.store', $request->access_token), [
            'answers' => [$questions[0]->id => 5],
        ]);

        $this->post(route('assessment.store', $request->access_token), [
            'answers' => [
                $questions[0]->id => 2, // diperbaiki
                $questions[1]->id => 4,
                $questions[2]->id => 4,
            ],
        ])->assertRedirect(route('assessment.thankyou'));

        $fresh = $request->fresh();

        $this->assertTrue($fresh->is_completed);
        $this->assertNotNull($fresh->completed_at);
        $this->assertSame(3, $fresh->responses()->count());
        $this->assertDatabaseHas('assessment_responses', [
            'assessment_request_id' => $request->id,
            'master_question_id' => $questions[0]->id,
            'score' => 2,
        ]);
    }

    public function test_kirim_ulang_setelah_selesai_ditolak_403(): void
    {
        [$request, $questions] = $this->tokenWithQuestions(1);

        $this->post(route('assessment.store', $request->access_token), [
            'answers' => [$questions[0]->id => 4],
        ]);

        $this->post(route('assessment.store', $request->access_token), [
            'answers' => [$questions[0]->id => 1],
        ])->assertForbidden();

        $this->assertDatabaseHas('assessment_responses', [
            'assessment_request_id' => $request->id,
            'score' => 4,
        ]);
    }

    private function tokenWithQuestions(int $count): array
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $period = AssessmentPeriod::forceCreate(['name' => 'Periode Uji', 'is_active' => true]);

        $request = AssessmentRequest::forceCreate([
            'user_id' => $dosen->id,
            'assessment_period_id' => $period->id,
            'assessor_name' => 'Atasan Uji',
            'assessor_email' => 'atasan@example.test',
            'relationship' => 'superior',
            'access_token' => Str::random(40),
            'is_completed' => false,
        ]);

        $questions = collect(range(1, $count))->map(fn (int $i) => MasterQuestion::forceCreate([
            'dimension' => 'Profesionalisme',
            'statement_self' => 'Pernyataan self '.$i,
            'statement_other' => 'Pernyataan other '.$i,
        ]));

        return [$request, $questions];
    }
}
