<?php

namespace App\Services;

use App\Models\AssessmentRequest;
use App\Models\AssessmentResponse;
use App\Models\MasterQuestion;
use Illuminate\Support\Facades\DB;

class AssessmentSubmissionService
{
    /**
     * Simpan jawaban; token hanya ditandai selesai bila semua pertanyaan terjawab.
     *
     * @param  array<int|string, int>  $answers
     * @return array{total: int, answered: int, remaining: int, completed: bool}
     */
    public function submit(AssessmentRequest $assessmentRequest, array $answers): array
    {
        if ($assessmentRequest->is_completed) {
            abort(403, 'Penilaian ini sudah diselesaikan sebelumnya.');
        }

        return DB::transaction(function () use ($assessmentRequest, $answers) {
            foreach ($answers as $questionId => $score) {
                AssessmentResponse::updateOrCreate(
                    [
                        'assessment_request_id' => $assessmentRequest->id,
                        'master_question_id' => $questionId,
                    ],
                    ['score' => $score]
                );
            }

            $total = MasterQuestion::count();
            $answered = $assessmentRequest->responses()->count();
            $completed = $total > 0 && $answered >= $total;

            if ($completed) {
                $assessmentRequest->update([
                    'is_completed' => true,
                    'completed_at' => now(),
                ]);
            }

            return [
                'total' => $total,
                'answered' => $answered,
                'remaining' => max(0, $total - $answered),
                'completed' => $completed,
            ];
        });
    }
}
