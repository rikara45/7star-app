<?php

namespace App\Http\Controllers;

use App\Exceptions\AssessmentCompletedException;
use App\Models\AssessmentRequest;
use App\Models\MasterQuestion;
use App\Services\AssessmentSubmissionService;
use App\Support\AssessmentAnswerRules;
use Illuminate\Http\Request;

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
    public function store(Request $request, string $token)
    {
        // Token diresolusikan lebih dulu: token tak dikenal harus 404, bukan
        // balasan validasi yang membocorkan format payload.
        $assessmentRequest = AssessmentRequest::where('access_token', $token)->firstOrFail();

        if ($assessmentRequest->is_completed) {
            return redirect()
                ->route('assessment.show', $token)
                ->with('error', 'Penilaian ini sudah diselesaikan sebelumnya.');
        }

        $validated = $request->validate(
            AssessmentAnswerRules::rules(),
            AssessmentAnswerRules::messages()
        );

        try {
            $result = $this->submissions->submit($assessmentRequest, $validated['answers']);
        } catch (AssessmentCompletedException $e) {
            // Kiriman lain menyelesaikan token ini lebih dulu.
            return redirect()
                ->route('assessment.show', $token)
                ->with('error', $e->getMessage());
        }

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
