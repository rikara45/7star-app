<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Portfolio;
use Illuminate\Http\Request;
use App\Http\Controllers\PortfolioController;
use App\Models\UniversitySetting;
use App\Models\AiAnalysis;
use App\Models\AssessmentPeriod;
use App\Http\Controllers\UserDashboardController;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class JurusanController extends Controller
{
    // 1. HALAMAN DASHBOARD (Hanya Statistik)
    public function dashboard()
    {
        // Statistik Ringkas
        $totalDosen = User::where('role', 'dosen')->count();
        
        // Hitung total item portofolio yang statusnya masih 'uploaded' (belum diverifikasi)
        $totalPendingItems = Portfolio::where('status', 'uploaded')->count();
        
        // Hitung total item yang sudah verified
        $totalVerifiedItems = Portfolio::where('status', 'verified')->count();

        return view('jurusan.dashboard', compact('totalDosen', 'totalPendingItems', 'totalVerifiedItems'));
    }

    // 2. HALAMAN LIST VERIFIKASI (Tabel Dosen)
    public function verificationIndex(Request $request)
    {
        $query = User::where('role', 'dosen')
            ->withCount(['portfolios as pending_count' => function($q){
                $q->where('status', 'uploaded');
            }])
            // Eager Load analisis terakhir agar kita tahu dosen ini sudah ada hasil atau belum
            ->with(['aiAnalyses' => function($q) {
                $q->latest();
            }]);

        // Search & Filter (Logika Lama Tetap Dipakai)
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        if ($request->status == 'pending') {
            $query->having('pending_count', '>', 0);
        }

        $dosens = $query->orderBy('pending_count', 'desc')->paginate(10);

        return view('jurusan.verifikasi.index', compact('dosens'));
    }

    // 3. HALAMAN DETAIL (Form Penilaian)
    public function show($userId)
    {
        $dosen = User::findOrFail($userId);
        $portfolios = Portfolio::where('user_id', $userId)->get()->keyBy('category');
        $categories = PortfolioController::getCategories();

        return view('jurusan.verifikasi.detail', compact('dosen', 'portfolios', 'categories'));
    }

    // 4. PROSES SIMPAN NILAI
    public function storeScore(Request $request, $portfolioId)
    {
        $request->validate([
            'score' => 'required|numeric|min:0|max:10',
        ]);

        $portfolio = Portfolio::findOrFail($portfolioId);
        
        $portfolio->update([
            'score' => $request->score,
            'status' => 'verified',
        ]);

        // --- LOGIKA BARU UNTUK AJAX ---
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'message' => 'Nilai berhasil disimpan!',
                'new_status' => 'verified',
                'new_score' => $request->score
            ]);
        }

        // Fallback untuk non-javascript (tetap redirect)
        return back()->with('success', 'Nilai berhasil disimpan.');
    }

    public function viewResult($userId)
    {
        $dosen = User::findOrFail($userId);
        $analysis = \App\Models\AiAnalysis::where('user_id', $userId)->latest()->first();

        if (!$analysis) {
            return back()->with('error', 'Dosen ini belum melakukan proses analisis hasil.');
        }

        // --- LOGIKA HITUNG RINCIAN (AGAR ACCORDION MUNCUL) ---
        $period = \App\Models\AssessmentPeriod::where('is_active', true)->first();
        
        $rawScores = \Illuminate\Support\Facades\DB::table('assessment_responses')
            ->join('assessment_requests', 'assessment_responses.assessment_request_id', '=', 'assessment_requests.id')
            ->join('master_questions', 'assessment_responses.master_question_id', '=', 'master_questions.id')
            ->where('assessment_requests.user_id', $userId)
            ->where('assessment_requests.assessment_period_id', $period->id)
            ->where('assessment_requests.is_completed', true)
            ->select('master_questions.dimension', \Illuminate\Support\Facades\DB::raw('AVG(assessment_responses.score) as avg_score'))
            ->groupBy('master_questions.dimension', 'assessment_requests.relationship')
            ->get();

        $dimensionDetails = [];
        if ($rawScores->isNotEmpty()) {
            $groupedByDimension = $rawScores->groupBy('dimension');
            foreach ($groupedByDimension as $dimension => $items) {
                $dimensionDetails[$dimension] = $items->avg('avg_score');
            }
        }
        // -----------------------------------------------------

        return view('jurusan.verifikasi.result', compact('dosen', 'analysis', 'dimensionDetails'));
    }

    public function exportPdf($userId)
    {
        $dosen = User::findOrFail($userId);
        
        // 1. Ambil Hasil Analisis
        $analysis = AiAnalysis::where('user_id', $userId)->latest()->first();
        if (!$analysis) {
            return back()->with('error', 'Dosen ini belum memproses hasil analisis.');
        }

        // 2. Ambil Setting Kampus
        $setting = UniversitySetting::first();

        // 3. Hitung Ulang Rincian Skor (Agar detail bar chart muncul)
        
        $period = AssessmentPeriod::where('is_active', true)->first();
        
        // --- LOGIKA HITUNG SKOR (COPY DARI USERDASHBOARD) ---
        $rawScores = \Illuminate\Support\Facades\DB::table('assessment_responses')
            ->join('assessment_requests', 'assessment_responses.assessment_request_id', '=', 'assessment_requests.id')
            ->join('master_questions', 'assessment_responses.master_question_id', '=', 'master_questions.id')
            ->where('assessment_requests.user_id', $userId) // Pakai ID Dosen
            ->where('assessment_requests.assessment_period_id', $period->id)
            ->where('assessment_requests.is_completed', true)
            ->select('master_questions.dimension', DB::raw('AVG(assessment_responses.score) as avg_score'))
            ->groupBy('master_questions.dimension', 'assessment_requests.relationship')
            ->get();

        $finalDimensionScores = [];
        if ($rawScores->isNotEmpty()) {
            $groupedByDimension = $rawScores->groupBy('dimension');
            foreach ($groupedByDimension as $dimension => $items) {
                $finalDimensionScores[$dimension] = $items->avg('avg_score');
            }
        }
        // ----------------------------------------------------

        $dimensionDetails = $finalDimensionScores;
        $user = $dosen; // Variable view butuh nama $user

        // 4. Load View PDF yang SAMA dengan milik Dosen
        $pdf = Pdf::loadView('pdf.report', compact('user', 'analysis', 'setting', 'dimensionDetails'));
        
        // 5. Download
        return $pdf->download('Laporan_' . $dosen->name . '.pdf');
    }
}