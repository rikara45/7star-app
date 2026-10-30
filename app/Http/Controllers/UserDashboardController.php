<?php

namespace App\Http\Controllers;

use App\Models\AiAnalysis;
use App\Models\AssessmentPeriod;
use App\Models\AssessmentRequest;
use App\Services\AssessmentScoreService;
use App\Support\PortfolioCategories;
use Illuminate\Support\Facades\Auth;

class UserDashboardController extends Controller
{
    public function __construct(private AssessmentScoreService $scoreService) {}

    // =========================================================================
    // HALAMAN DASHBOARD UTAMA
    // =========================================================================
    public function index()
    {
        $user = Auth::user();
        $period = AssessmentPeriod::where('is_active', true)->first();

        if (! $period) {
            return abort(404, 'Periode penilaian belum aktif.');
        }

        // 1. Ambil Data Request
        $requests = AssessmentRequest::where('user_id', $user->id)
            ->where('assessment_period_id', $period->id)
            ->get();
        $selfRequest = $requests->where('relationship', 'self')->first();

        // Hitung Undangan (Total & Completed)
        $countSuperior = $requests->where('relationship', 'superior')->count();
        $countPeer = $requests->where('relationship', 'peer')->count();

        $completedSuperior = $requests->where('relationship', 'superior')->where('is_completed', true)->count();
        $completedPeer = $requests->where('relationship', 'peer')->where('is_completed', true)->count();

        // Validasi Status Self
        $isSelfDone = $selfRequest && $selfRequest->is_completed;

        // 2. Ambil Data Portofolio (Pakai Fungsi Privat)
        $categories = PortfolioCategories::all();
        $realtimeData = $this->scoreService->calculate($user, $period);

        $myPortfolios = $realtimeData['portfolios']->keyBy('category');
        $currentPortfolioScore = $realtimeData['portfolioScore'];

        // Status Upload
        $uploadedPortfolioCount = $myPortfolios->count();
        $pendingCount = $myPortfolios->where('status', 'uploaded')->count(); // Menunggu Jurusan
        $missingCount = count($categories) - $uploadedPortfolioCount;

        // --- SYARAT MINIMAL (VALIDASI KETAT) ---
        $isMinRequirementsMet = $isSelfDone
                                && ($completedSuperior >= 1)
                                && ($completedPeer >= 3)
                                && ($uploadedPortfolioCount >= 3);

        // --- CEK PERUBAHAN DATA (CHANGE DETECTION) ---
        // PERBAIKAN DI SINI: Ganti nama variabel jadi $latestAnalysis (bukan $lastAnalysis)
        $latestAnalysis = AiAnalysis::where('user_id', $user->id)
            ->where('assessment_period_id', $period->id)
            ->latest()
            ->first();

        $hasDataChanged = true; // Default: Tombol Proses Muncul

        // Jika data lama ada DAN skornya sama persis -> Berarti TIDAK BERUBAH
        if ($latestAnalysis &&
            abs($latestAnalysis->score_behavior - $realtimeData['behaviorScore100']) < 0.01 &&
            abs($latestAnalysis->score_portfolio - $realtimeData['portfolioScore']) < 0.01 &&
            ! empty($latestAnalysis->ai_narrative)) {

            $hasDataChanged = false; // Tombol jadi "Lihat Hasil"
        }

        return view('dashboard_7stars', compact(
            'requests', 'selfRequest', 'period',
            'myPortfolios', 'categories', 'currentPortfolioScore',
            'pendingCount', 'missingCount',
            'countSuperior', 'countPeer',
            'isSelfDone', 'hasDataChanged',
            'latestAnalysis', // <--- Sekarang ini sudah cocok dengan variabel di atas
            'isMinRequirementsMet', 'completedSuperior', 'completedPeer', 'uploadedPortfolioCount'
        ));
    }
}
