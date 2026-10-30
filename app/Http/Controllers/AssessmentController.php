<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitAssessmentRequest;
use App\Models\AssessmentRequest;
use App\Models\MasterQuestion;
use App\Services\AssessmentSubmissionService;

class AssessmentController extends Controller
{
    public function __construct(private AssessmentSubmissionService $submissions) {}

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

        // Jawaban yang sudah tersimpan, dipakai untuk menandai pilihan saat dilanjutkan
        $savedAnswers = $assessmentRequest->responses()->pluck('score', 'master_question_id');

        return view('assessment.form', compact('assessmentRequest', 'groupedQuestions', 'token', 'savedAnswers'));
    }

    // 2. Menyimpan Jawaban ke Database
    public function store(SubmitAssessmentRequest $request, $token)
    {
        $assessmentRequest = AssessmentRequest::where('access_token', $token)->firstOrFail();

        $result = $this->submissions->submit($assessmentRequest, $request->validated('answers'));

        if ($result['completed']) {
            return redirect()->route('assessment.thankyou');
        }

        return redirect()
            ->route('assessment.show', $token)
            ->with('status', sprintf(
                'Jawaban tersimpan (%d dari %d). Masih ada %d pertanyaan.',
                $result['answered'],
                $result['total'],
                $result['remaining']
            ));
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
