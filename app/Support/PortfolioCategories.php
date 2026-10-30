<?php

namespace App\Support;

class PortfolioCategories
{
    /**
     * Nama kategori => petunjuk dokumen yang diharapkan.
     *
     * @return array<string, string>
     */
    public static function all(): array
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
