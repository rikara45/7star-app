<?php

namespace App\Services;

use App\Exceptions\AssessmentCompletedException;
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
        return DB::transaction(function () use ($assessmentRequest, $answers) {
            // Kunci baris request agar dua submit bersamaan tidak lolos gate bersama.
            $locked = AssessmentRequest::whereKey($assessmentRequest->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->is_completed) {
                throw new AssessmentCompletedException('Penilaian ini sudah diselesaikan sebelumnya.');
            }

            foreach ($answers as $questionId => $score) {
                AssessmentResponse::updateOrCreate(
                    [
                        'assessment_request_id' => $locked->id,
                        'master_question_id' => (int) $questionId,
                    ],
                    ['score' => $score]
                );
            }

            $total = MasterQuestion::count();

            // Hitung pertanyaan berbeda yang masih ada di master_questions. Menghitung
            // baris saja akan mengunci token parsial bila ada duplikat atau baris yatim
            // dari data lama (create() tanpa updateOrCreate, atau seeder truncate).
            $answered = $locked->responses()
                ->whereIn('master_question_id', MasterQuestion::query()->select('id'))
                ->distinct()
                ->count('master_question_id');

            $completed = $total > 0 && $answered === $total;

            if ($completed) {
                $locked->update([
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
