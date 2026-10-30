<?php

namespace App\Http\Controllers;

use App\Models\AssessmentPeriod;
use App\Models\Portfolio;
use App\Models\UniversitySetting;
use App\Models\User;
use App\Services\AssessmentResultService;
use App\Support\PortfolioCategories;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class JurusanController extends Controller
{
    public function __construct(private AssessmentResultService $results) {}

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
        // Kita ambil data Dosen
        $query = User::where('role', 'dosen')
            // 1. Hitung Dokumen Pending (Status 'uploaded')
            ->withCount(['portfolios as pending_count' => function ($q) {
                $q->where('status', 'uploaded');
            }])
            // 2. Hitung Total Dokumen (PENTING: Untuk tahu dia sudah kirim atau belum)
            ->withCount('portfolios as total_portfolios')

            // Load analisis AI (opsional, bawaan kode lama Anda)
            ->with(['aiAnalyses' => function ($q) {
                $q->latest();
            }]);

        // Search
        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        // Filter Status
        if ($request->status == 'pending') {
            $query->having('pending_count', '>', 0);
        }

        $dosens = $query->orderBy('pending_count', 'desc')->paginate(10);

        // Kirim variabel '$dosens' (BUKAN $assessments)
        return view('jurusan.verifikasi.index', compact('dosens'));
    }

    // 3. HALAMAN DETAIL (Form Penilaian)
    public function show($userId)
    {
        $dosen = $this->dosenOrFail($userId);
        $portfolios = Portfolio::where('user_id', $userId)->get()->keyBy('category');
        $categories = PortfolioCategories::all();

        return view('jurusan.verifikasi.detail', compact('dosen', 'portfolios', 'categories'));
    }

    // 4. PROSES SIMPAN NILAI
    public function storeScore(Request $request, $portfolioId)
    {
        $request->validate([
            'score' => 'required|numeric|min:0|max:10',
        ]);

        // Portofolio hanya milik dosen; id mentah tidak boleh menembus role lain.
        $portfolio = Portfolio::whereKey($portfolioId)
            ->whereHas('user', fn ($query) => $query->where('role', 'dosen'))
            ->firstOrFail();

        $portfolio->update([
            'score' => $request->score,
            'status' => 'verified',
        ]);

        // --- LOGIKA BARU UNTUK AJAX ---
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'message' => 'Nilai berhasil disimpan!',
                'new_status' => 'verified',
                'new_score' => $request->score,
            ]);
        }

        // Fallback untuk non-javascript (tetap redirect)
        return back()->with('success', 'Nilai berhasil disimpan.');
    }

    public function viewResult($userId)
    {
        $dosen = $this->dosenOrFail($userId);
        $period = AssessmentPeriod::where('is_active', true)->first();

        if (! $period) {
            return back()->with('error', 'Tidak ada periode penilaian aktif.');
        }

        $data = $this->results->viewDataFor($dosen, $period);

        if ($data === null) {
            return back()->with('error', 'Dosen ini belum melakukan proses analisis hasil.');
        }

        return view('jurusan.verifikasi.result', [
            'dosen' => $dosen,
            'analysis' => $data['analysis'],
            'dimensionDetails' => $data['dimensionDetails'],
        ]);
    }

    public function exportPdf($userId)
    {
        $dosen = $this->dosenOrFail($userId);
        $period = AssessmentPeriod::where('is_active', true)->first();

        if (! $period) {
            return back()->with('error', 'Tidak ada periode penilaian aktif.');
        }

        $data = $this->results->viewDataFor($dosen, $period);

        if ($data === null) {
            return back()->with('error', 'Dosen ini belum memproses hasil analisis.');
        }

        // View PDF yang SAMA dengan milik dosen, supaya angka tidak bisa berbeda.
        $pdf = Pdf::loadView('pdf.report', [
            'user' => $dosen,
            'analysis' => $data['analysis'],
            'setting' => UniversitySetting::current(),
            'dimensionDetails' => $data['dimensionDetails'],
        ]);

        return $pdf->download('Laporan_'.$dosen->name.'.pdf');
    }

    /**
     * Semua halaman jurusan hanya untuk data dosen yang dinilai.
     */
    private function dosenOrFail($userId): User
    {
        return User::where('role', 'dosen')->findOrFail($userId);
    }
}
