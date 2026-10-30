# Rencana Irisan 7 — Tampilan dan Konsistensi

**Tujuan:** memecah `dashboard_7stars.blade.php` menjadi partial yang bisa dibaca, menyatukan deret
bintang dan badge status ke komponen Blade bersama, dan mengganti `abort(404)` untuk "tidak ada
periode aktif" menjadi halaman yang menjelaskan keadaan itu.

**Arsitektur:** view induk hanya menyusun urutan partial dan menaruh skrip salin link; semua data
dihitung di controller. `x-star-rating` dan `x-status-badge` dipakai di dashboard dan dua halaman
hasil. Tidak ada query atau perhitungan bisnis di Blade.

**Stack:** Laravel 12, Blade, PHPUnit feature test, SQLite in-memory.

**Spec induk:** `specs/2026-10-30-refactor-arsitektur-design.md` bagian "Irisan 7".

## Batasan Global

- Tidak menambah dependency; tidak memakai queue; tidak ada JavaScript baru di luar Alpine yang
  sudah dipakai.
- Nama route dan URL tidak berubah.
- `tests/Feature/ScoringCharacterizationTest.php` tidak disentuh.
- `php artisan test` hijau dan `php vendor/bin/pint --test app tests` PASS.
- `resources/views/pdf/report.blade.php` tidak memakai komponen baru: DomPDF tidak memuat Tailwind,
  jadi markup dan CSS inline-nya dibiarkan apa adanya.

## Hasil

Pemecahan view (sebelum → sesudah):

| Berkas | Baris |
| --- | --- |
| `resources/views/dashboard_7stars.blade.php` | 348 → 47 |
| `resources/views/dashboard/self-assessment.blade.php` | — → 37 |
| `resources/views/dashboard/invite-form.blade.php` | — → 33 |
| `resources/views/dashboard/assessor-list.blade.php` | 65 |
| `resources/views/dashboard/portfolio.blade.php` | 86 |
| `resources/views/dashboard/actions.blade.php` | 89 |
| `resources/views/dashboard/no-period.blade.php` | 22 |
| `resources/views/components/star-rating.blade.php` | 14 |
| `resources/views/components/status-badge.blade.php` | 24 |

Perilaku yang sengaja dipertahankan:

- Urutan bagian, teks label, kuota `Atasan (n/2)` / `Sejawat (n/5)`, tombol salin tautan, konfirmasi
  batal undangan, modal "Konfirmasi Proses", dan overlay "Sedang Menganalisis" sama seperti sebelum
  pemecahan.
- Deret bintang tetap memakai `filter_var(..., FILTER_SANITIZE_NUMBER_INT)` sehingga string
  `"4 Bintang"` menghasilkan 4 bintang terisi dan 3 `text-gray-200`.

Perubahan yang disengaja (dicatat):

- `dashboard.index` tanpa periode aktif mengembalikan **200** dengan halaman
  `dashboard/no-period`, bukan `abort(404)`. Tidak ada periode aktif adalah keadaan normal di luar
  jadwal penilaian; 404 membuat dosen mengira halamannya rusak.
- Controller mengirim `$assessorCount` (jumlah undangan selain `self`) supaya partial tidak
  menghitung ulang data yang sudah ada di controller.
- Badge status kini satu komponen: `subtle` memakai latar 50 (Selesai/Pending di daftar penilai),
  varian tegas memakai latar 200 (Verified/Pending di kartu portofolio), ukuran `xs` untuk kartu
  portofolio, dan `pulse` untuk status yang menunggu verifikasi.

## Verifikasi

- `php artisan test` → 150 passed (532 assertions); Pint PASS 74 files.
- `tests/Feature/DashboardViewTest.php` (5 test) mengunci: bagian utama dashboard + kuota + badge,
  dashboard tanpa periode aktif 200, periode tidak aktif 200, 4 bintang terisi/3 kosong, dan warna
  badge per tone.
- Browser (`php artisan serve --port=8088`, dosen uji dengan 1 atasan selesai, 1 sejawat pending,
  1 portofolio verified, 1 pending, 1 analisis 4 bintang):
  1. `/dashboard-penilaian`: lima bagian tampil, `Total: 2`, badge `Selesai` (hijau 50), `Pending`
     (abu subtle), `Verified (4)` (hijau 200), `Pending (0)` (biru 200 + pulse), `Belum Upload` (abu),
     panel validasi menghitung 1 atasan/0 sejawat/2 portofolio.
  2. `/hasil-penilaian`: 4 bintang terisi, 3 bintang `text-gray-200`, narasi AI tampil.
  3. Periode dinonaktifkan sementara → `/dashboard-penilaian` menampilkan "Periode penilaian belum
     aktif" tanpa form undangan, lalu periode diaktifkan kembali.
  4. Tidak ada error konsol selain Vite dev server yang memang tidak dijalankan.

## Di luar cakupan (risiko dicatat)

- `resources/views/jurusan/verifikasi/detail.blade.php` dan `index.blade.php` masih menulis badge
  dan statusnya sendiri; migrasi ke `x-status-badge` belum dilakukan karena warna dan teksnya
  menyatu dengan penanda item yang sedang dinilai.
- `resources/views/pdf/report.blade.php` tetap memakai markup bintang sendiri (alasan DomPDF di atas).
- Spec induk menyebut partial "statistik"; yang dibuat adalah header daftar penilai dengan
  `Total: n` karena dashboard ini tidak punya kartu statistik terpisah.
