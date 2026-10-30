<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePortfolioRequest;
use App\Models\Portfolio;
use Illuminate\Support\Facades\Storage;

class PortfolioController extends Controller
{
    // Daftar Kategori sesuai Dokumen Rubrik Penilaian
    private $categories = [
        'Pendidikan & Sertifikasi',
        'Pengalaman Jabatan',
        'Kinerja Akademik',
        'Riset & Kolaborasi',
        'Inovasi Institusional',
        'Empowerment & Mentoring',
        'Integritas & Teladan',
        'Kapasitas Digital',
        'Komunikasi Dua Arah',
        'Dampak Eksternal',
    ];

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

    // Helper untuk mengambil list kategori beserta hint-nya
    public static function getCategories()
    {
        return [
            'Pendidikan & Sertifikasi' => 'Ijazah, sertifikat pelatihan, Serdos',
            'Pengalaman Jabatan' => 'SK jabatan, bukti memimpin proyek',
            'Kinerja Akademik' => 'Jurnal Scopus, buku, HKI',
            'Riset & Kolaborasi' => 'MoU/MoA, proposal riset, output',
            'Inovasi Institusional' => 'Inovasi pembelajaran, SOP digital',
            'Empowerment & Mentoring' => 'Bukti coaching, mentoring staf/mahasiswa',
            'Integritas & Teladan' => 'Rekam etik, disiplin, penghargaan',
            'Kapasitas Digital' => 'Bukti implementasi platform digital',
            'Komunikasi Dua Arah' => 'Notula, tindak lanjut masukan',
            'Dampak Eksternal' => 'Akreditasi, ranking, kontribusi publik',
        ];
    }
}
