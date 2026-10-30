<?php

namespace App\Http\Controllers;

use App\Mail\AssessmentInvitation;
use App\Models\AiAnalysis;
use App\Models\AssessmentPeriod;
use App\Models\AssessmentRequest;
use App\Services\AssessmentScoreService;
use App\Support\PortfolioCategories;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

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

    // =========================================================================
    // UNDANG PENILAI
    // =========================================================================
    public function invite(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'relationship' => 'required|in:superior,peer,self',
        ]);

        $user = Auth::user();
        $period = AssessmentPeriod::where('is_active', true)->firstOrFail();

        $currentCount = AssessmentRequest::where('user_id', $user->id)
            ->where('assessment_period_id', $period->id)
            ->where('relationship', $request->relationship)
            ->count();

        if ($request->relationship == 'superior' && $currentCount >= 2) {
            return back()->with('error', 'Batas Tercapai: Anda hanya boleh mengundang maksimal 2 Atasan.');
        }
        if ($request->relationship == 'peer' && $currentCount >= 5) {
            return back()->with('error', 'Batas Tercapai: Anda hanya boleh mengundang maksimal 5 Teman Sejawat.');
        }

        $exists = AssessmentRequest::where('user_id', $user->id)
            ->where('assessment_period_id', $period->id)
            ->where('relationship', $request->relationship)
            ->where('assessor_email', $request->email)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Email orang ini sudah Anda undang sebelumnya.');
        }

        $newRequest = AssessmentRequest::create([
            'user_id' => $user->id,
            'assessment_period_id' => $period->id,
            'assessor_name' => $request->name,
            'assessor_email' => $request->email,
            'relationship' => $request->relationship,
            'access_token' => Str::random(32),
        ]);

        // --- TAMBAHAN BARU: KIRIM EMAIL ---
        try {
            Mail::to($request->email)->send(new AssessmentInvitation($newRequest));
        } catch (\Exception $e) {
            // Jika email gagal kirim (misal koneksi error), jangan crash, tapi catat log saja
            // Aplikasi tetap lanjut sukses menyimpan data
            Log::error('Gagal kirim email undangan: '.$e->getMessage());

            // Opsional: Beri pesan sukses tapi ada catatan
            return back()->with('success', 'Undangan dibuat, tapi Email gagal terkirim. Silakan bagikan Link Manual.');
        }

        return back()->with('success', 'Undangan berhasil dibuat & email telah dikirim!');
    }

    // =========================================================================
    // HAPUS UNDANGAN (CANCEL INVITE)
    // =========================================================================
    public function destroyRequest($id)
    {
        $request = AssessmentRequest::findOrFail($id);

        if ($request->user_id != Auth::id()) {
            abort(403);
        }

        if ($request->is_completed) {
            return back()->with('error', 'Tidak bisa membatalkan penilaian yang sudah selesai.');
        }

        $request->delete();

        return back()->with('success', 'Undangan berhasil dibatalkan.');
    }
}
