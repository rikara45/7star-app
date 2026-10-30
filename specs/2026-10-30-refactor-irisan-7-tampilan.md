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
  portofolio, dan `pulse` untuk status yang menunggu verifikasi. Efek samping tampilan: badge
  `Pending` di daftar penilai berubah dari `font-medium` ke `font-bold`, dari `flex` ke `inline-flex`,
  dan bertambah border 1px sehingga lebar/tingginya bergeser sekitar 2px.
- Warna teks badge abu naik dari `text-gray-400` ke `text-gray-600`: `text-gray-400` di atas
  `bg-gray-100` hanya sekitar 2:1 dan gagal kontras AA, padahal dipakai untuk "Belum Upload"
  berukuran 10px.
- Dua tombol ikon di daftar penilai (salin tautan, batalkan undangan) mendapat `aria-label` berisi
  nama penilai, `focus-visible` outline, dan padding 6px; sebelumnya hanya `title` dan tanpa cincin
  fokus.
- `$period` tidak lagi dikirim ke view (tidak dipakai satu pun partial); `$assessorCount` dihitung
  dari `$countSuperior + $countPeer` supaya hanya ada satu cara menghitung hal yang sama.
- Form self-assessment memakai `$user` dari controller, bukan `Auth::user()` di dalam Blade.

## Verifikasi

- `php artisan test` → 151 passed (548 assertions); Pint PASS 74 files.
- `tests/Feature/DashboardViewTest.php` (6 test) mengunci: bagian utama dashboard + kuota + kelas
  badge komponen, keadaan syarat belum terpenuhi, tombol proses saat syarat terpenuhi, dashboard
  tanpa periode aktif 200, data periode tidak aktif tidak tampil, 4 bintang terisi/3 kosong, dan
  warna badge per tone. Kuota dipatok lewat `config()` di `setUp` supaya tidak bergantung `.env`.
- `AssessmentResultTest` dan `JurusanVerificationScoringTest` kini menghitung 7 bintang dan jumlah
  bintang kosong dari HTML halaman hasil, jadi kerusakan `x-star-rating` tidak lolos.
- Mutasi yang dibuktikan memerahkan test: `$filled = 0` pada `x-star-rating` → 3 test gagal;
  `@if(!$isMinRequirementsMet || $pendingCount > 0)` diganti `@if(true)` → test tombol proses gagal.
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
- Halaman `dashboard/no-period` tidak punya tautan aksi; `InvitationController@store` juga masih
  `firstOrFail()` → 404 untuk keadaan "tidak ada periode aktif" yang sama. Menyatukan pesannya
  perlu keputusan tersendiri karena menyentuh alur undangan.
- Partial diletakkan di `resources/views/dashboard/`, bukan `dashboard/partials/`; namanya sudah
  dibedakan dari `dashboard.blade.php` bawaan Breeze dan `dashboard_7stars.blade.php`.
