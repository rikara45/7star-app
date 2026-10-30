# Refactor Arsitektur Coba-App (7 Star Leadership Assessment)

Tanggal: 2026-10-30
Status: menunggu persetujuan

## 1. Intent dan alasan

Aplikasi ini tumbuh cepat tanpa lapisan struktur: satu controller memikul enam tanggung jawab,
daftar kategori portofolio punya dua sumber kebenaran, hampir tidak ada FormRequest, dan
beberapa keputusan berbahaya (unggahan publik, kategori tanpa whitelist, ID pertanyaan tanpa
verifikasi) masih terbuka. Akibatnya setiap perubahan kecil berisiko, dan pembaca baru tidak
bisa menebak di mana aturan bisnis sebenarnya berada.

Tujuan: menjadikan aturan bisnis punya satu tempat yang jelas, sehingga perubahan perilaku
berikutnya bisa dilakukan tanpa membaca 300 baris controller, dan memperbaiki temuan berbahaya
yang sudah teridentifikasi.

### Temuan saat verifikasi dokumen

`AssessmentController@store` menulis `completed_at` saat menandai penilaian selesai, tetapi tabel
`assessment_requests` tidak punya kolom itu (migration
`2025_11_20_092529_create_seven_stars_tables.php` hanya membuat `is_completed`).

Diverifikasi langsung terhadap database pengembangan: Eloquent membuang atribut itu diam-diam —
`fill(['is_completed' => true, 'completed_at' => now()])` hanya menandai `is_completed` sebagai
dirty, `UPDATE` berhasil, dan `completed_at` tidak pernah tersimpan. Tidak ada exception, tidak
ada rollback, dan data penilaian yang ada (10 request selesai, 270 jawaban) membuktikan alur
pengiriman berjalan normal.

Jadi ini bukan bug fatal, melainkan **dead code**: niat mencatat waktu selesai tidak pernah
terwujud. Tidak ada satu pun tempat di kode yang membaca `completed_at`. Diputuskan: kolomnya
ditambahkan pada irisan 2 agar niat itu berfungsi, sekaligus memungkinkan audit waktu selesai.

## 2. Goals

- Setiap aturan bisnis punya satu lokasi: service/action untuk perilaku, FormRequest untuk
  validasi input, policy untuk otorisasi, model untuk relasi dan cast.
- Controller kembali jadi orkestrasi tipis: terima request tervalidasi, panggil satu service,
  kembalikan response.
- Portofolio tidak lagi dapat diunduh publik; hanya pemilik, jurusan, dan admin.
- Kategori portofolio hanya dari satu daftar; kategori di luar daftar ditolak validasi.
- Pengiriman penilaian via token tidak bisa menyebabkan error 500 karena ID pertanyaan palsu.
- Perilaku skor, bobot, dan ambang bintang tidak berubah sama sekali.

## 3. Non-goals

- Tidak mengganti stack: tetap Blade + Tailwind, tanpa Livewire/Inertia/React.
- Tidak menambah REST API, queue job, atau scheduler.
- Tidak mendesain ulang bahasa visual atau struktur navigasi.
- Tidak mengubah bobot 70/30, skala 1-5, atau ambang bintang (dikunci
  `tests/Feature/ScoringCharacterizationTest.php`).
- Tidak mengganti nama route publik yang sudah dipakai tautan email (`assessment.show`,
  `assessment.store`, `result.show`, `result.generate`, `jurusan.*`).
- Tidak memindahkan logika ke frontend.

## 4. Anti-goals (gagal meski "jalan")

- Refactor yang membuat test skor harus diubah agar lulus.
- Lapisan abstraksi yang hanya memindahkan kode tanpa mengurangi duplikasi atau pengetahuan
  yang tersebar.
- Perubahan besar tanpa kemungkinan berhenti di tengah dengan kondisi repo tetap sehat.
- Perbaikan keamanan yang menukar kemudahan deploy shared hosting dengan ketergantungan baru
  (misalnya memaksa symlink atau worker).

## 5. Constraints

- Target deploy: shared hosting Domainesia (cPanel, tanpa SSH/root, tanpa queue worker) dan VPS
  Coolify. Semua solusi harus jalan tanpa worker.
- Email undangan dikirim sinkron di dalam request; kegagalan email tidak boleh menggagalkan
  penyimpanan undangan (perilaku sekarang dipertahankan).
- MySQL/MariaDB di produksi, SQLite in-memory untuk test.
- `php artisan test` sudah jalan native (pdo_sqlite aktif), `php vendor/bin/pint --test app tests`
  sebagai gerbang gaya.
- Bahasa UI Indonesia; identifier kode Inggris.
- Tanpa dependency baru.

## 6. Pendekatan yang dipertimbangkan

**A. Irisan vertikal per domain (dipilih).** Tujuh irisan yang masing-masing berdiri sendiri:
portofolio, penilaian via token, skor & hasil dosen, hasil jurusan, undangan, admin & pengaturan,
tampilan. Tiap
irisan menuntaskan FormRequest + service/action + policy + perbaikan perilaku + test untuk satu
domain, lalu merge. Kelebihan: setiap irisan bisa dirilis/dihentikan sendiri, perbaikan
keamanan paling berbahaya selesai lebih dulu, kegagalan terlokalisasi. Kekurangan: kerapian
menyeluruh baru terasa di irisan terakhir, dan ada lebih banyak commit.

**B. Lapisan dulu, besar sekaligus.** Seluruh FormRequest/service/policy dibuat dulu, perilaku
diperbaiki di akhir. Kelebihan: hasil akhir paling konsisten. Kekurangan: tidak ada nilai produk
sampai ujung, dan berhenti di tengah meninggalkan dua gaya kode sekaligus. Ditolak.

**C. Inti aman saja.** Hanya pecah controller terbesar, satu sumber kategori, unggahan privat,
validasi ID pertanyaan. Kelebihan: paling cepat. Kekurangan: validasi tetap tersebar, tidak
memenuhi tujuan "aturan bisnis punya satu tempat". Ditolak.

## 7. Rancangan per irisan

Urutan dipilih berdasarkan risiko: yang membocorkan data dikerjakan lebih dulu.

### Irisan 1 — Portofolio dan unggahan (risiko tertinggi)

- Satu sumber kategori: kelas `App\Support\PortfolioCategories` dengan `all(): array<string,string>`
  (nama => hint). Hapus array privat `$categories` di `PortfolioController` dan
  `getCategories()` statis; semua pemanggil memakai kelas itu.
- `App\Http\Requests\StorePortfolioRequest`: `category` wajib dan harus salah satu key
  `PortfolioCategories::all()`; `file` wajib, `mimes:pdf,jpg,jpeg,png`, `max:2048`; `description`
  nullable string dengan batas panjang.
- Unggahan disimpan di disk privat (`local`, root `storage/app/private/portfolios`), bukan disk
  `public`.
- Route baru `GET /portofolio/{portfolio}/berkas` (`portfolio.file`) + `PortfolioPolicy@download`:
  pemilik, role `jurusan`, dan role `admin` boleh; dosen lain 403.
- Berkas lama yang sudah ada di disk `public` tetap bisa diunduh lewat route baru: resolver mencoba
  disk privat lalu disk publik. Tidak ada perpindahan berkas otomatis (tidak ada akses ke data
  produksi dari sini); dicatat sebagai tugas operator.
- Test: kategori di luar daftar → 422; unggahan valid tersimpan di disk privat dan tidak ada di
  disk publik; pemilik/jurusan/admin bisa unduh; dosen lain 403; berkas legacy di disk publik
  tetap terunduh.

### Irisan 2 — Penilaian via token (publik, tanpa login)

- Migration baru: tambahkan `completed_at` (nullable timestamp) ke `assessment_requests`, lalu
  backfill `completed_at = updated_at` untuk baris `is_completed = true` yang masih null. Dengan
  begitu penulisan waktu selesai yang saat ini dibuang diam-diam oleh Eloquent menjadi berfungsi.
  Gunakan penjaga `Schema::hasColumn()` agar aman bila kolom sudah ada manual di produksi.
- `App\Http\Requests\SubmitAssessmentRequest`: `answers` array wajib dan minimal berisi satu
  jawaban; `answers.*` integer 1..5; setiap key harus ID yang ada di `master_questions`.
- `App\Services\AssessmentSubmissionService`: cari request via token (`firstOrFail`), tolak bila
  sudah selesai (403). Simpan jawaban dengan `updateOrCreate` per pertanyaan sehingga pengiriman
  ulang memperbaiki jawaban, bukan menggandakannya. Tandai `is_completed` + `completed_at` **hanya
  bila seluruh baris `master_questions` sudah terjawab**; selain itu token tetap terbuka untuk
  dilanjutkan. Semua dalam satu transaksi.
- Setelah simpan: bila belum lengkap, kembali ke form dengan pesan "Jawaban tersimpan (x dari y).
  Masih ada z pertanyaan."; bila lengkap, arahkan ke halaman terima kasih seperti sekarang.
- Test: kirim lengkap → semua jawaban tersimpan, `is_completed` dan `completed_at` terisi, diarahkan
  ke halaman terima kasih; kirim sebagian → tersimpan, **tidak** selesai, kembali ke form dengan
  pesan sisa; lanjutkan pengiriman sebagian lalu lengkapi → selesai tanpa jawaban ganda; kirim
  ulang setelah selesai → 403; ID pertanyaan palsu → 422 (bukan 500); array kosong → 422; skor
  0/6 → 422.

### Irisan 3 — Skor dan hasil (dosen)

- Pecah `UserDashboardController` (312 baris) menjadi:
  - `AssessmentResultController` untuk `result.show`, `result.generate`, `result.pdf`
  - `UserDashboardController` untuk `dashboard.index`, `invite`, `invite.destroy`
- `App\Services\AssessmentResultService`: menyiapkan data tampilan hasil (analisis tersimpan +
  rincian dimensi realtime) untuk `showResult` dan `exportPdf` dosen, sehingga keduanya tidak bisa
  berbeda hasilnya.
- Logika "apakah hasil masih berlaku" (`abs(score - stored) < 0.01`) dan orkestrasi
  `generateResult` (susun prompt, panggil `AiNarrativeService`, upsert `AiAnalysis`) pindah ke
  service yang sama (`generateFor(User, AssessmentPeriod)`), sehingga controller tinggal menerima
  hasil dan mengembalikan redirect.
- Analisis tersimpan tetap diambil dengan `latest()` lintas periode, sesuai perilaku sekarang.
- `AssessmentScoreService` dan `AiNarrativeService` tidak diubah.
- Test: seluruh test karakterisasi tetap hijau tanpa diubah; tambah test bahwa halaman hasil dan
  PDF dosen memakai rincian dimensi yang sama.

### Irisan 4 — Hasil jurusan

- `JurusanController@viewResult` dan `@exportPdf` memakai `AssessmentResultService` dari irisan 3,
  sehingga halaman hasil jurusan dan dosen berbagi satu jalur perhitungan.
- `JurusanController` tinggal berisi daftar verifikasi, penilaian portofolio (`storeScore`), dan
  pemanggilan service untuk hasil.
- Test: rincian dimensi yang dilihat jurusan identik dengan yang dilihat dosen untuk data yang
  sama (memperluas test paritas yang sudah ada); PDF jurusan memakai data yang sama.

### Irisan 5 — Undangan penilai

- `StoreInvitationRequest` (name, email, relationship `in:superior,peer,self`).
- `App\Services\InvitationService`: batas 2 atasan / 5 sejawat, cek undangan ganda per email,
  pembuatan token, dan pengiriman email yang kegagalannya hanya dicatat (perilaku sekarang).
- Batas dan panjang token pindah ke `config/assessment.php` (`invitation.max_superior`,
  `max_peer`, `token_length`).
- `destroyRequest` pindah ke `InvitationController@destroy` dengan policy: hanya pemilik, dan
  tidak boleh menghapus yang sudah selesai. Route `invite.destroy` dipertahankan namanya.
- Test: batas terlampaui ditolak; email ganda ditolak; kegagalan email tidak menggagalkan
  penyimpanan; hapus milik orang lain → 403; hapus yang selesai → ditolak.

### Irisan 6 — Admin dan pengaturan universitas

- FormRequest untuk CRUD user admin dan update pengaturan.
- `UserManagementService` untuk pembuatan/perubahan user (hash password, role valid).
- Policy: hanya `admin` (middleware sudah ada; policy menjaga aksi langsung).
- Test: admin bisa CRUD; non-admin 403; validasi role salah ditolak; update pengaturan tervalidasi.

### Irisan 7 — Tampilan dan konsistensi

- `dashboard_7stars.blade.php` (336 baris) dipecah menjadi partial: statistik, portofolio,
  undangan, aksi. Tidak boleh ada query atau perhitungan bisnis di dalam Blade.
- Komponen Blade bersama untuk bintang dan badge status (`x-star-rating`, `x-status-badge`) dipakai
  di dashboard dan halaman hasil. `resources/views/pdf/report.blade.php` tidak memakai komponen
  itu karena DomPDF tidak memuat Tailwind; ia mempertahankan markup dan CSS inline-nya sendiri.
- "Tidak ada periode aktif" pada `dashboard.index` berhenti memakai `abort(404)`; tampilkan
  halaman `dashboard/no-period` dengan pesan Indonesia dan status 200.
- Test: halaman no-period tampil dengan pesan; dashboard tetap memuat data yang sama seperti
  sebelum pemecahan partial (assert beberapa variabel kunci).

## 8. Konvensi yang berlaku di semua irisan

- FormRequest di `app/Http/Requests`, bernama `<Aksi><Entitas>Request`.
- Service di `app/Services`; satu service boleh punya beberapa method, tapi tidak menyentuh
  `Request`/HTTP.
- Policy di `app/Policies` (auto-discovery Laravel), satu policy per model yang punya aturan akses.
- Model: `$fillable` eksplisit, relasi Eloquent dideklarasikan, cast untuk tanggal/boolean.
- Tidak ada `DB::table()` di controller. Query kompleks boleh di service atau scope model.
- Tidak ada `env()` di luar `config/`.
- Pesan validasi dan UI Bahasa Indonesia.
- Setiap irisan: branch sendiri, commit atomik, `php artisan test` hijau, `pint --test` hijau.

## 9. Verifikasi

- Otomatis: `php artisan test` (suite penuh, minimal 49 test saat ini harus tetap hijau) dan
  `php vendor/bin/pint --test app tests`.
- Test baru per irisan seperti disebut di atas.
- Manual (tidak bisa diotomatiskan penuh, harus dilaporkan apa adanya): unggah + unduh berkas
  nyata lewat browser, kirim penilaian lewat token nyata, buka PDF.
- Definition of done per irisan: controller domain tersebut di bawah ~120 baris, tidak ada
  duplikasi daftar kategori/batas undangan, test hijau, Pint hijau.

## 10. Risiko dan mitigasi

- **Berkas produksi sudah publik.** Setelah irisan 1, URL lama `/storage/portfolios/...` tidak
  lagi dilayani aplikasi (tetap ada di disk sampai operator memindahkan). Mitigasi: route unduh
  baru melayani kedua disk, dan langkah pemindahan didokumentasikan untuk operator.
- **Perubahan arti `is_completed`.** Dulu satu kali kirim = selesai; sekarang selesai hanya bila
  semua pertanyaan terjawab. Token yang sebelumnya terkunci setelah kiriman parsial akan menjadi
  terbuka dan bisa dilengkapi — perubahan perilaku yang disengaja. Skor dimensi tetap dihitung dari
  jawaban yang ada, jadi hasil yang belum lengkap masih mungkin dihitung bila dosen memproses lebih
  dulu; ini tidak menambah penanda kelengkapan pada skor.
- **Migration baru di produksi.** Irisan 2 menambah kolom `completed_at`; deploy berikutnya wajib
  menjalankan `php artisan migrate`. Backfill hanya mengisi baris yang sudah `is_completed`.
- **Perubahan nama controller/route param.** Nama route dipertahankan; yang berubah hanya kelas
  dan nama parameter binding, sehingga tautan email tetap valid.
- **Refactor menyentuh perhitungan skor.** Dikunci test karakterisasi; setiap irisan yang
  menyentuh jalur skor wajib menjalankan test itu tanpa mengubahnya.

## 11. Yang diserahkan ke pelaksana

- Penamaan kelas internal, granularitas partial Blade, dan apakah perlu ViewModel.
- Apakah policy didaftarkan manual atau lewat auto-discovery.
- Bentuk pesan validasi yang spesifik.
- Urutan teknis di dalam satu irisan (mana yang diekstrak lebih dulu).
- Keputusan untuk memakai `Route::resource` atau route eksplisit pada irisan 6.

## 12. Keputusan yang diambil setelah builder check

Enam pertanyaan muncul saat dokumen ini diuji dengan reviewer berkonteks bersih. Jawabannya:

1. **`completed_at` tidak ada di skema.** Diverifikasi: penulisan itu dibuang diam-diam oleh
   Eloquent, alur penilaian tetap berjalan. Diputuskan: tambah kolom nullable + backfill dari
   `updated_at` untuk baris yang sudah selesai (irisan 2).
2. **Definisi "pertanyaan aktif".** Diputuskan: validasi memakai semua baris `master_questions`
   sebagai rujukan ID yang sah. Pengiriman sebagian diterima dan bisa dilanjutkan; token baru
   ditandai selesai ketika seluruh pertanyaan terjawab, sehingga satu pengiriman yang tidak sengaja
   terpotong tidak lagi mengunci penilaian. Tidak menambah kolom `is_active`, karena filter baru di
   query skor berisiko menggeser angka yang dikunci test karakterisasi.
3. **Cakupan irisan 3.** Diputuskan: dibatasi ke hasil dosen; hasil jurusan menjadi irisan 4
   tersendiri yang memakai service yang sama.
4. **Logika `generateResult`.** Diputuskan: pindah ke service (`generateFor`), karena controller
   baru harus di bawah ~120 baris dan aturan bisnis dikumpulkan di satu tempat.
5. **Analisis lintas periode.** Diputuskan: tetap `latest()` seperti sekarang. Risiko narasi
   periode lama tampil bersama rincian periode baru diterima dan dicatat di bagian risiko.
6. **Komponen di PDF.** Diputuskan: komponen bersama hanya untuk dashboard dan halaman hasil;
   PDF mempertahankan markup inline-nya sendiri karena DomPDF tanpa Tailwind.

## 13. Catatan untuk operator (di luar kode)

- Setelah irisan 1, berkas portofolio baru berada di `storage/app/private/portfolios`. Berkas lama
  di `storage/app/public/portfolios` tetap terunduh lewat route baru, tetapi masih bisa diakses
  publik lewat URL `/storage/...` sampai dipindahkan manual. Pemindahan adalah tugas operator.
- Refactor ini menambah satu migration (`completed_at` pada irisan 2); deploy sesudahnya wajib
  menjalankan `php artisan migrate`.
