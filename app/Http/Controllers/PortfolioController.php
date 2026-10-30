<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePortfolioRequest;
use App\Models\Portfolio;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PortfolioController extends Controller
{
    public function store(StorePortfolioRequest $request)
    {
        $portfolio = Portfolio::firstOrNew([
            'user_id' => $request->user()->id,
            'category' => $request->validated('category'),
        ]);

        try {
            $filePath = $request->file('file')->store('portfolios', 'local');
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Berkas gagal disimpan. Coba lagi atau hubungi admin.');
        }

        if ($filePath === false) {
            return back()->with('error', 'Berkas gagal disimpan. Coba lagi atau hubungi admin.');
        }

        try {
            DB::transaction(function () use ($portfolio, $request, $filePath) {
                $portfolio->fill([
                    'file_path' => $filePath,
                    'status' => 'uploaded', // Status awal
                    'score' => 0, // JANGAN KASIH 10 DULU. Tunggu Jurusan.
                ]);

                if ($request->has('description')) {
                    $portfolio->description = $request->validated('description');
                }

                $portfolio->save();
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($filePath);

            throw $e;
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
