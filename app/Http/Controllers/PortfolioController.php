<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        'Dampak Eksternal'
    ];

    public function store(Request $request)
    {
        $request->validate([
            'category' => 'required|string',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048', // Max 2MB
            'description' => 'nullable|string'
        ]);

        $user = Auth::user();

        // Upload File
        if ($request->hasFile('file')) {
            // Simpan ke folder: storage/app/public/portfolios
            $filePath = $request->file('file')->store('portfolios', 'public');

            // Simpan ke Database
            // Kita pakai updateOrCreate agar user bisa menimpa file lama jika upload lagi di kategori yang sama
            Portfolio::updateOrCreate(
                ['user_id' => $user->id, 'category' => $request->category],
                [
                    'file_path' => $filePath,
                    'description' => $request->description,
                    'status' => 'uploaded', // Status awal
                    'score' => 0 // JANGAN KASIH 10 DULU. Tunggu Jurusan.
                ]
            );

            return back()->with('success_portfolio', 'Bukti portofolio berhasil diunggah!');
        }

        return back()->with('error', 'File gagal diunggah.');
    }
    
    // Helper untuk mengambil list kategori beserta hint-nya
    public static function getCategories() {
        return [
            'Pendidikan & Sertifikasi'  => 'Ijazah, sertifikat pelatihan, Serdos',
            'Pengalaman Jabatan'        => 'SK jabatan, bukti memimpin proyek',
            'Kinerja Akademik'          => 'Jurnal Scopus, buku, HKI',
            'Riset & Kolaborasi'        => 'MoU/MoA, proposal riset, output',
            'Inovasi Institusional'     => 'Inovasi pembelajaran, SOP digital',
            'Empowerment & Mentoring'   => 'Bukti coaching, mentoring staf/mahasiswa',
            'Integritas & Teladan'      => 'Rekam etik, disiplin, penghargaan',
            'Kapasitas Digital'         => 'Bukti implementasi platform digital',
            'Komunikasi Dua Arah'       => 'Notula, tindak lanjut masukan',
            'Dampak Eksternal'          => 'Akreditasi, ranking, kontribusi publik'
        ];
    }
}