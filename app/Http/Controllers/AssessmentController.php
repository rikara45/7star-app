<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitAssessmentRequest;
use App\Models\AssessmentRequest;
use App\Models\AssessmentResponse;
use App\Models\MasterQuestion;
use Illuminate\Support\Facades\DB;

class AssessmentController extends Controller
{
    // 1. Menampilkan Form Penilaian berdasarkan Token
    public function show($token)
    {
        // Cari data request berdasarkan token
        $assessmentRequest = AssessmentRequest::with('user')
            ->where('access_token', $token)
            ->firstOrFail();

        // Cek apakah sudah pernah diisi
        if ($assessmentRequest->is_completed) {
            return view('assessment.completed'); // Tampilkan halaman "Sudah Selesai"
        }

        // Ambil semua pertanyaan dari database
        $questions = MasterQuestion::all();

        // Kelompokkan pertanyaan berdasarkan dimensinya (agar rapi di tampilan)
        $groupedQuestions = $questions->groupBy('dimension');

        return view('assessment.form', compact('assessmentRequest', 'groupedQuestions', 'token'));
    }

    // 2. Menyimpan Jawaban ke Database
    public function store(SubmitAssessmentRequest $request, $token)
    {
        $assessmentRequest = AssessmentRequest::where('access_token', $token)->firstOrFail();

        if ($assessmentRequest->is_completed) {
            abort(403, 'Penilaian ini sudah diselesaikan sebelumnya.');
        }

        $data = $request->validated();

        DB::transaction(function () use ($assessmentRequest, $data) {
            // Simpan setiap jawaban
            foreach ($data['answers'] as $questionId => $score) {
                AssessmentResponse::create([
                    'assessment_request_id' => $assessmentRequest->id,
                    'master_question_id' => $questionId,
                    'score' => $score,
                ]);
            }

            // Tandai request ini sudah selesai
            $assessmentRequest->update([
                'is_completed' => true,
                'completed_at' => now(),
            ]);
        });

        return redirect()->route('assessment.thankyou');
    }

    public function thankyou()
    {
        return view('assessment.thankyou');
    }

    public function completed()
    {
        return view('assessment.completed');
    }
}
