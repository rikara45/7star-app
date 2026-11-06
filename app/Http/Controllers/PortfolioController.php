<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePortfolioRequest;
use App\Models\AssessmentPeriod;
use App\Models\Portfolio;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PortfolioController extends Controller
{
    private const VERIFIED_MESSAGE = 'Portofolio ini sudah diverifikasi jurusan sehingga tidak dapat diganti. Hubungi admin bila perlu perbaikan.';

    public function store(StorePortfolioRequest $request)
    {
        $period = AssessmentPeriod::where('is_active', true)->orderBy('id')->first();

        if ($period === null) {
            return back()->with('error_portfolio', 'Tidak ada periode penilaian aktif. Hubungi admin.');
        }

        // forPeriod menyertakan baris warisan tanpa periode supaya portofolio
        // verified lama tetap tidak bisa ditimpa.
        $portfolio = Portfolio::where('user_id', $request->user()->id)
            ->where('category', $request->validated('category'))
            ->forPeriod($period->id)
            ->first();

        if ($portfolio === null) {
            $portfolio = new Portfolio([
                'user_id' => $request->user()->id,
                'assessment_period_id' => $period->id,
                'category' => $request->validated('category'),
            ]);
        }

        // Cek awal hanya untuk menghindari penyimpanan berkas yang pasti ditolak;
        // keputusan akhir diambil ulang di dalam transaksi dengan lockForUpdate.
        if ($portfolio->exists && $portfolio->status === 'verified') {
            return back()->with('error_portfolio', self::VERIFIED_MESSAGE);
        }

        try {
            $filePath = $request->file('file')->store('portfolios', 'local');
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error_portfolio', 'Berkas gagal disimpan. Coba lagi atau hubungi admin.');
        }

        if ($filePath === false) {
            return back()->with('error_portfolio', 'Berkas gagal disimpan. Coba lagi atau hubungi admin.');
        }

        $oldPath = $portfolio->exists ? $portfolio->file_path : null;

        try {
            $blocked = DB::transaction(function () use ($portfolio, $request, $filePath) {
                if ($portfolio->exists) {
                    $locked = Portfolio::whereKey($portfolio->getKey())->lockForUpdate()->first();

                    if ($locked === null) {
                        return 'Portofolio berubah saat diproses. Silakan unggah ulang.';
                    }

                    if ($locked->status === 'verified') {
                        return self::VERIFIED_MESSAGE;
                    }

                    $portfolio = $locked;
                }

                $portfolio->fill([
                    'file_path' => $filePath,
                    'status' => 'uploaded', // Status awal
                    'score' => 0, // JANGAN KASIH 10 DULU. Tunggu Jurusan.
                ]);

                if ($request->has('description')) {
                    $portfolio->description = $request->validated('description');
                }

                $portfolio->save();

                return null;
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($filePath);

            throw $e;
        }

        if ($blocked !== null) {
            Storage::disk('local')->delete($filePath);

            return back()->with('error_portfolio', $blocked);
        }

        // Berkas lama baru dihapus setelah baris baru tersimpan, supaya kegagalan
        // transaksi tidak menghilangkan bukti yang masih dipakai.
        if ($oldPath !== null && $oldPath !== $filePath) {
            Storage::disk('local')->delete($oldPath);
        }

        return back()->with('success_portfolio', 'Bukti portofolio berhasil diunggah!');
    }

    public function download(Portfolio $portfolio)
    {
        $this->authorize('download', $portfolio);

        $disk = collect(['local', 'public'])
            ->first(fn (string $disk) => Storage::disk($disk)->exists($portfolio->file_path));

        abort_if($disk === null, 404);

        return Storage::disk($disk)->response($portfolio->file_path);
    }
}
