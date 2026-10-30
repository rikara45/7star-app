<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePortfolioRequest;
use App\Models\Portfolio;
use Illuminate\Support\Facades\Storage;

class PortfolioController extends Controller
{
    public function store(StorePortfolioRequest $request)
    {
        $filePath = $request->file('file')->store('portfolios', 'local');

        // Kita pakai updateOrCreate agar user bisa menimpa file lama jika upload lagi di kategori yang sama
        Portfolio::updateOrCreate(
            ['user_id' => $request->user()->id, 'category' => $request->validated('category')],
            [
                'file_path' => $filePath,
                'description' => $request->validated('description'),
                'status' => 'uploaded', // Status awal
                'score' => 0, // JANGAN KASIH 10 DULU. Tunggu Jurusan.
            ]
        );

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
