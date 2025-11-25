<?php

namespace App\Http\Controllers;

use App\Models\AssessmentRequest;
use App\Models\AssessmentPeriod;
use App\Models\Portfolio;
use App\Models\AiAnalysis;
use App\Http\Controllers\PortfolioController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http; 
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Mail;
use App\Mail\AssessmentInvitation;

class UserDashboardController extends Controller
{
    // =========================================================================
    // FUNGSI PRIVAT: Menghitung Skor (Dipakai di Dashboard & Tombol Proses)
    // =========================================================================
    private function calculateRealtimeScores($user, $period)
    {
        $rawScores = DB::table('assessment_responses')
            ->join('assessment_requests', 'assessment_responses.assessment_request_id', '=', 'assessment_requests.id')
            ->join('master_questions', 'assessment_responses.master_question_id', '=', 'master_questions.id')
            ->where('assessment_requests.user_id', $user->id)
            ->where('assessment_requests.assessment_period_id', $period->id)
            ->where('assessment_requests.is_completed', true)
            ->select(
                'master_questions.dimension', 
                'assessment_requests.relationship',
                DB::raw('AVG(assessment_responses.score) as avg_score')
            )
            ->groupBy('master_questions.dimension', 'assessment_requests.relationship')
            ->get();

        $behaviorScore100 = 0;
        $dimensionString = "Data penilaian 360 belum lengkap.";
        $finalDimensionScores = []; // Array untuk menyimpan rincian

        if ($rawScores->isNotEmpty()) {
            $groupedByDimension = $rawScores->groupBy('dimension');

            foreach ($groupedByDimension as $dimension => $items) {
                $finalDimensionScores[$dimension] = $items->avg('avg_score');
            }

            $averageBehavior = collect($finalDimensionScores)->avg();
            $behaviorScore100 = ($averageBehavior / 5) * 100;

            $dimensionString = collect($finalDimensionScores)->map(function($score, $dimName) {
                return "- {$dimName}: " . number_format($score, 2) . "/5.0";
            })->implode("\n");
        }

        // Hitung Portofolio & Akhir
        $portfolios = Portfolio::where('user_id', $user->id)->get();
        $portfolioScore = min($portfolios->sum('score'), 100);
        $finalScore = number_format(($behaviorScore100 * 0.70) + ($portfolioScore * 0.30), 2);

        // Tentukan Bintang
        if ($finalScore >= 91) $stars = "7 Bintang";
        elseif ($finalScore >= 86) $stars = "6 Bintang";
        elseif ($finalScore >= 80) $stars = "5 Bintang";
        elseif ($finalScore >= 72) $stars = "4 Bintang";
        elseif ($finalScore >= 65) $stars = "3 Bintang";
        elseif ($finalScore >= 55) $stars = "2 Bintang";
        else $stars = "1 Bintang";

        return [
            'behaviorScore100' => $behaviorScore100,
            'portfolioScore' => $portfolioScore,
            'finalScore' => $finalScore,
            'stars' => $stars,
            'dimensionString' => $dimensionString,
            'portfolios' => $portfolios,
            'dimensionDetails' => $finalDimensionScores // <--- INI DATA BARU YG KITA BUTUHKAN
        ];
    }

    // =========================================================================
    // HALAMAN DASHBOARD UTAMA
    // =========================================================================
    public function index()
    {
        $user = Auth::user();
        $period = AssessmentPeriod::where('is_active', true)->first();

        if (!$period) {
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
        $categories = PortfolioController::getCategories();
        $realtimeData = $this->calculateRealtimeScores($user, $period);
        
        $myPortfolios = $realtimeData['portfolios']->keyBy('category');
        $currentPortfolioScore = $realtimeData['portfolioScore'];

        // Status Upload
        $uploadedPortfolioCount = $myPortfolios->count();
        $pendingCount = $myPortfolios->where('status', 'uploaded')->count(); // Menunggu Jurusan
        $missingCount = count($categories) - $uploadedPortfolioCount;

        // --- SYARAT MINIMAL (VALIDASI KETAT) ---
        // 1. Self Selesai
        // 2. Min 1 Atasan Selesai
        // 3. Min 3 Sejawat Selesai
        // 4. Min 3 Portofolio Diupload
        $isMinRequirementsMet = $isSelfDone 
                                && ($completedSuperior >= 1) 
                                && ($completedPeer >= 3)
                                && ($uploadedPortfolioCount >= 3);

        // --- CEK PERUBAHAN DATA (CHANGE DETECTION) ---
        $lastAnalysis = AiAnalysis::where('user_id', $user->id)
                        ->where('assessment_period_id', $period->id)
                        ->latest()
                        ->first();

        $hasDataChanged = true; // Default: Tombol Proses Muncul

        // Jika data lama ada DAN skornya sama persis -> Berarti TIDAK BERUBAH
        if ($lastAnalysis && 
            abs($lastAnalysis->score_behavior - $realtimeData['behaviorScore100']) < 0.01 && 
            abs($lastAnalysis->score_portfolio - $realtimeData['portfolioScore']) < 0.01 &&
            !empty($lastAnalysis->ai_narrative)) {
            
            $hasDataChanged = false; // Tombol jadi "Lihat Hasil"
        }

        return view('dashboard_7stars', compact(
            'requests', 'selfRequest', 'period', 
            'myPortfolios', 'categories', 'currentPortfolioScore',
            'pendingCount', 'missingCount',
            'countSuperior', 'countPeer',
            'isSelfDone', 'hasDataChanged',
            // Variabel Syarat Minimal
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
            Log::error('Gagal kirim email undangan: ' . $e->getMessage());
            
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

        if ($request->user_id != Auth::id()) abort(403);
        
        if ($request->is_completed) {
            return back()->with('error', 'Tidak bisa membatalkan penilaian yang sudah selesai.');
        }

        $request->delete();
        return back()->with('success', 'Undangan berhasil dibatalkan.');
    }

    // =========================================================================
    // PROSES HASIL & AI (GEMINI 2.0 FLASH + TIMEOUT 20S)
    // =========================================================================
    public function generateResult()
    {
        $user = Auth::user();
        $period = AssessmentPeriod::where('is_active', true)->first();

        if (!$period) return back()->with('error', 'Tidak ada periode aktif.');

        // 1. Ambil Data Realtime
        $data = $this->calculateRealtimeScores($user, $period);

        // 2. Cek Perubahan (Server Side Check)
        $lastAnalysis = AiAnalysis::where('user_id', $user->id)
                        ->where('assessment_period_id', $period->id)
                        ->latest()
                        ->first();

        if ($lastAnalysis && 
            abs($lastAnalysis->score_behavior - $data['behaviorScore100']) < 0.01 && 
            abs($lastAnalysis->score_portfolio - $data['portfolioScore']) < 0.01 &&
            !empty($lastAnalysis->ai_narrative)) {
            
            return redirect()->route('result.show');
        }

        // 3. Siapkan Prompt Portofolio
        $allCategories = PortfolioController::getCategories();
        $portfolioStringLines = [];

        foreach($allCategories as $catName => $catHint) {
            $p = $data['portfolios']->firstWhere('category', $catName);
            if ($p) {
                $status = $p->status == 'verified' ? 'Terverifikasi' : 'Menunggu Verifikasi';
                $portfolioStringLines[] = "- {$catName}: SUDAH ADA ({$status}, Poin: {$p->score})";
            } else {
                $portfolioStringLines[] = "- {$catName}: BELUM DIKUMPULKAN (Poin: 0)";
            }
        }
        $portfolioDetailString = implode("\n", $portfolioStringLines);

        // --- INTEGRASI AI (GEMINI 2.0 FLASH) ---
        $aiNarrative = null; // Inisialisasi NULL agar aman
        
        $prompt = "Bertindaklah sebagai Konsultan SDM Universitas profesional. 
        DATA PROFIL:
        Nama: {$user->name}
        Total Skor Akhir: {$data['finalScore']} (Level {$data['stars']})
        
        RINCIAN SKOR 360 (PERILAKU):
        {$data['dimensionString']}

        RINCIAN PORTOFOLIO (BUKTI FISIK):
        {$portfolioDetailString}

        TUGAS:
        Berikan analisis naratif dalam Bahasa Indonesia yang mencakup:
        1. **Analisis Kekuatan**: Apa keunggulan utama dari perilaku dan portofolio yang sudah ada?
        2. **Celah Kinerja (Gap)**: Soroti portofolio apa yang BELUM dikumpulkan atau nilai 360 yang rendah.
        3. **Rekomendasi**: 3 langkah konkret untuk melengkapi kekurangan tersebut.";

        try {
            $apiKey = env('GEMINI_API_KEY');

            if ($apiKey) {
                // PERUBAHAN: Timeout dinaikkan jadi 60 detik
                $response = Http::timeout(60) 
                    ->connectTimeout(10)      
                    ->withoutVerifying()
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}", [
                        'contents' => [['parts' => [['text' => $prompt]]]]
                    ]);

                if ($response->successful()) {
                    $json = $response->json();
                    if(isset($json['candidates'][0]['content']['parts'][0]['text'])) {
                        $aiNarrative = $json['candidates'][0]['content']['parts'][0]['text'];
                    }
                }
            }

        } catch (ConnectionException $e) {
            // Update pesan errornya juga agar sesuai
            $aiNarrative = "Waktu analisis habis (timeout > 60 detik). Silakan coba lagi nanti.";
            Log::error('Gemini Timeout: ' . $e->getMessage());
            
        } catch (\Exception $e) {
            Log::error('Gemini API Error: ' . $e->getMessage());
        }

        // Safety Net
        if (empty($aiNarrative)) {
             $aiNarrative = "Analisis AI belum tersedia saat ini. Namun skor Anda telah berhasil dihitung dan disimpan.";
        }

        // 5. Simpan Hasil
        AiAnalysis::updateOrCreate(
            ['user_id' => $user->id, 'assessment_period_id' => $period->id],
            [
                'score_behavior' => $data['behaviorScore100'],
                'score_portfolio' => $data['portfolioScore'],
                'final_score' => $data['finalScore'],
                'star_rating' => $data['stars'],
                'ai_narrative' => $aiNarrative
            ]
        );

        return redirect()->route('result.show');
    }

    public function showResult()
    {
        $user = Auth::user();
        $period = AssessmentPeriod::where('is_active', true)->first(); // Pastikan ada period

        // Ambil hasil simpanan DB
        $analysis = AiAnalysis::where('user_id', $user->id)->latest()->first();

        if (!$analysis) {
            return redirect()->route('dashboard.index')->with('error', 'Belum ada hasil. Silakan proses dulu.');
        }

        // Hitung ulang rincian detail agar bisa ditampilkan di view
        // (Kita hitung on-the-fly agar tidak perlu ubah struktur tabel database)
        $realtimeData = $this->calculateRealtimeScores($user, $period);
        $dimensionDetails = $realtimeData['dimensionDetails'];

        return view('result_7stars', compact('analysis', 'dimensionDetails'));
    }
}