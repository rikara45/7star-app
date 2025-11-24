<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Portfolio;
use Illuminate\Http\Request;
use App\Http\Controllers\PortfolioController;

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
        // Mulai Query User Dosen
        $query = User::where('role', 'dosen')
            // Hitung jumlah portofolio pending per dosen
            ->withCount(['portfolios as pending_count' => function($q){
                $q->where('status', 'uploaded');
            }]);

        // FITUR 1: Pencarian Nama
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // FITUR 2: Filter Status (Hanya yang butuh verifikasi / Semua)
        if ($request->status == 'pending') {
            // Ambil dosen yang punya minimal 1 pending item
            $query->having('pending_count', '>', 0);
        }

        // Urutkan: Yang paling banyak pending-nya ditaruh paling atas
        $dosens = $query->orderBy('pending_count', 'desc')->paginate(10); // Pakai Pagination biar ringan

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
}