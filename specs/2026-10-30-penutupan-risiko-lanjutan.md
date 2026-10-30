# Penutupan Sisa Risiko Lanjutan — Rencana Implementasi

> **Untuk pelaksana:** kerjakan task berurutan; setiap task punya siklus test sendiri. Spec acuan:
> `specs/2026-10-30-refactor-arsitektur-design.md` dan spec irisan 1–7.

**Tujuan:** menutup sebelas risiko yang masih terbuka setelah refactor vertical slice, tanpa
menyentuh bobot/ambang skor yang dikunci `ScoringCharacterizationTest`.

**Arsitektur:** perubahan tersebar di empat kelompok: jalur penilaian publik, perhitungan & PDF,
otorisasi jurusan, dan undangan/route. Aturan bisnis tetap di service; controller hanya
menerjemahkan hasil ke response.

**Stack:** Laravel 11 (Blade, Vite, Tailwind), MySQL/MariaDB produksi, SQLite in-memory untuk test,
DomPDF untuk laporan. Tanpa dependency baru, tanpa queue worker.

## Batasan Global

- Bahasa UI dan pesan: Bahasa Indonesia.
- Tanpa dependency baru; tanpa queue worker; tetap bisa jalan di shared hosting.
- `tests/Feature/ScoringCharacterizationTest.php` tidak boleh diubah dan harus tetap hijau.
- Pint wajib PASS untuk `app` dan `tests`.
- Satu commit = satu perubahan logis; commit di branch, merge `--no-ff` ke `main`, push hanya
  setelah izin pemilik repo.
- Perubahan perilaku yang disetujui pemilik repo:
  1. Kirim ulang token selesai: 403 → redirect dengan pesan error.
  2. Hapus undangan yang sudah punya jawaban ditolak.
  3. `AVG` skor memakai jawaban terbaru per pertanyaan (duplikat lama tidak menggandakan bobot).
  4. `{userId}` non-dosen dan portofolio bukan milik dosen → 404.

## Fokus Review

- Token selesai yang dikirim ulang bersamaan (dua request) → tidak boleh menyimpan jawaban baru.
- Token tidak dikenal + payload tidak valid → 404, bukan 422 (urutan resolusi token).
- Data jawaban duplikat lama → skor memakai baris terbaru, bukan rata-rata keduanya.
- Tabel `university_settings` kosong → PDF tetap bisa dicetak dengan kop fallback.
- Admin/jurusan mengakses `/dashboard-penilaian` → 403, bukan error 500 dari dashboard dosen.

---

### Task 1: Throttle endpoint penilaian publik

**Files:**
- Modify: `routes/web.php:103-104`
- Test: `tests/Feature/AssessmentThrottleTest.php` (baru)

**Interfaces:**
- Produces: route `assessment.store` dengan middleware `throttle:20,1`, `assessment.show` dengan
  `throttle:60,1`.

- [ ] **Step 1: Tulis test yang gagal**

```php
public function test_kirim_jawaban_dibatasi_dua_puluh_per_menit(): void
{
    for ($i = 0; $i < 20; $i++) {
        $this->post('/nilai/token-palsu', ['answers' => [1 => 3]])->assertNotFound();
    }

    $this->post('/nilai/token-palsu', ['answers' => [1 => 3]])->assertStatus(429);
}
```

- [ ] **Step 2: Jalankan test, pastikan gagal** (`php artisan test --filter=AssessmentThrottleTest`)
- [ ] **Step 3: Tambahkan middleware throttle di dua route `/nilai/{token}`**
- [ ] **Step 4: Jalankan test, pastikan lulus**
- [ ] **Step 5: Commit** `fix(penilaian): batasi laju akses endpoint token publik`

---

### Task 2: Token selesai tidak lagi `abort(403)` dari service

**Files:**
- Create: `app/Exceptions/AssessmentCompletedException.php`
- Modify: `app/Services/AssessmentSubmissionService.php`
- Modify: `app/Http/Controllers/AssessmentController.php`
- Modify: `resources/views/assessment/completed.blade.php`
- Test: `tests/Feature/AssessmentSubmissionTest.php` (ubah test 403 → redirect + pesan)

**Interfaces:**
- Produces: `AssessmentCompletedException extends RuntimeException`; service melempar exception
  domain, controller menangkap dan redirect ke `assessment.show` dengan flash `error`.

- [ ] **Step 1: Ubah test** `test_kirim_ulang_setelah_selesai_ditolak_403` menjadi
  `test_kirim_ulang_setelah_selesai_ditolak_dengan_pesan`: harapkan redirect ke
  `assessment.show` + `assertSessionHas('error', 'Penilaian ini sudah diselesaikan sebelumnya.')`,
  dan skor lama tidak berubah.
- [ ] **Step 2: Jalankan, pastikan merah**
- [ ] **Step 3: Buat exception, ganti `abort(403)` di service, tangkap di controller sebelum validasi**
- [ ] **Step 4: Tampilkan flash error di `assessment/completed.blade.php`**
- [ ] **Step 5: Jalankan `AssessmentSubmissionTest`, pastikan hijau**
- [ ] **Step 6: Commit** `refactor(penilaian): pindahkan penolakan token selesai ke controller`

---

### Task 3: Resolusi token sebelum validasi jawaban

**Files:**
- Create: `app/Support/AssessmentAnswerRules.php`
- Delete: `app/Http/Requests/SubmitAssessmentRequest.php`
- Modify: `app/Http/Controllers/AssessmentController.php`
- Test: `tests/Feature/AssessmentSubmissionTest.php`

**Interfaces:**
- Produces: `AssessmentAnswerRules::rules(): array` dan `::messages(): array` (statis, key error
  tetap `answers` dan `answers.<id>`).
- Controller `store(Request $request, string $token)` → `firstOrFail()` dulu, cek selesai, baru
  `$request->validate(...)`.

- [ ] **Step 1: Tulis test** `test_token_tidak_dikenal_tetap_404_walau_payload_tidak_valid`:
  POST `/nilai/token-palsu` dengan `answers => []` → `assertNotFound()`.
- [ ] **Step 2: Jalankan, pastikan merah (sekarang 302 + error validasi)**
- [ ] **Step 3: Pindahkan rules/messages ke `AssessmentAnswerRules`, hapus FormRequest, ubah controller**
- [ ] **Step 4: Jalankan seluruh `AssessmentSubmissionTest`, pastikan hijau**
- [ ] **Step 5: Commit** `fix(penilaian): dahulukan resolusi token sebelum validasi jawaban`

---

### Task 4: Skor mengabaikan baris jawaban duplikat

**Files:**
- Modify: `app/Services/AssessmentScoreService.php:35-53`
- Test: `tests/Feature/AssessmentScoreDuplicateTest.php` (baru)

**Interfaces:**
- Produces: `dimensionScores()` tetap `array<string, float>`, tetapi hanya memakai baris dengan
  `id` terbesar per `(assessment_request_id, master_question_id)`.

- [ ] **Step 1: Tulis test** dua baris untuk pertanyaan yang sama (skor 5 lalu 1) → dimensi bernilai
  `1.0` (baris terbaru), bukan `3.0`.
- [ ] **Step 2: Jalankan, pastikan merah**
- [ ] **Step 3: Implementasi `joinSub` subquery `MAX(id)` per grup di `dimensionScores()`**
- [ ] **Step 4: Jalankan test baru + `ScoringCharacterizationTest` (tanpa mengubah test itu)**
- [ ] **Step 5: Commit** `fix(skor): hitung rata-rata dari jawaban terbaru per pertanyaan`

---

### Task 5: `viewDataFor` tidak lagi menghitung portofolio

**Files:**
- Modify: `app/Services/AssessmentResultService.php:33-45`
- Test: `tests/Feature/AssessmentResultTest.php`

- [ ] **Step 1: Tulis test** `test_data_hasil_tidak_mengambil_portofolio`: `DB::enableQueryLog()`,
  panggil `viewDataFor()`, assert tidak ada query yang menyebut `portfolios`.
- [ ] **Step 2: Jalankan, pastikan merah**
- [ ] **Step 3: Ganti `calculate(...)['dimensionDetails']` dengan `dimensionScores($user->id, $period)`**
- [ ] **Step 4: Jalankan `AssessmentResultTest`, pastikan hijau**
- [ ] **Step 5: Commit** `refactor(hasil): ambil rincian dimensi tanpa memuat portofolio`

---

### Task 6: Kop PDF tetap tercetak saat pengaturan kosong

**Files:**
- Modify: `app/Models/UniversitySetting.php`
- Modify: `app/Http/Controllers/AssessmentResultController.php:60`
- Modify: `app/Http/Controllers/JurusanController.php` (`exportPdf`)
- Test: `tests/Feature/UniversitySettingTest.php`

**Interfaces:**
- Produces: `UniversitySetting::current(): self` — baris pertama, atau instance baru dengan
  `uni_name = 'Universitas'`, `uni_address = ''`, `uni_website = ''`, `uni_logo_path = null`.

- [ ] **Step 1: Tulis test** hapus semua baris `university_settings`, minta PDF dosen → `assertOk()`
  dan header `application/pdf`.
- [ ] **Step 2: Jalankan, pastikan merah**
- [ ] **Step 3: Tambah `current()` dan pakai di kedua controller**
- [ ] **Step 4: Jalankan test, pastikan hijau**
- [ ] **Step 5: Commit** `fix(laporan): sediakan kop fallback saat pengaturan kampus kosong`

---

### Task 7: Jurusan hanya bisa membuka data dosen

**Files:**
- Modify: `app/Http/Controllers/JurusanController.php` (`show`, `storeScore`, `viewResult`, `exportPdf`)
- Test: `tests/Feature/JurusanAuthorizationTest.php` (baru)

**Interfaces:**
- Produces: pencarian target selalu `User::where('role', 'dosen')->findOrFail($userId)`; portofolio
  hanya boleh dinilai bila pemiliknya ber-role `dosen`.

- [ ] **Step 1: Tulis test** `{userId}` milik admin dan jurusan → 404 untuk `show`, `result`, dan
  `result.pdf`; `storeScore` pada portofolio milik admin → 404.
- [ ] **Step 2: Jalankan, pastikan merah**
- [ ] **Step 3: Batasi query di empat method**
- [ ] **Step 4: Jalankan test baru + `JurusanViewTest` + `JurusanVerificationScoringTest`**
- [ ] **Step 5: Commit** `fix(jurusan): batasi akses data hanya untuk pengguna ber-role dosen`

---

### Task 8: Cek kuota undangan atomik

**Files:**
- Modify: `app/Services/InvitationService.php:30-50`
- Test: `tests/Feature/InvitationTest.php`

- [ ] **Step 1: Tambah test** `test_undangan_yang_ditolak_tidak_menyisakan_baris`: kuota atasan
  sudah penuh → POST → error, `assessment_requests` tetap 2, `Mail::assertNothingSent()`
  (mengunci bahwa pengecekan dan pembuatan berada di satu jalur).
- [ ] **Step 2: Jalankan, pastikan lulus sekarang (regresi), lalu ubah implementasi**
- [ ] **Step 3: Bungkus cek kuota + cek email + `create()` dalam `DB::transaction` dengan
  `User::whereKey($user->getKey())->lockForUpdate()->first()`; kirim email setelah transaksi selesai**
- [ ] **Step 4: Jalankan `InvitationTest`, pastikan hijau**
- [ ] **Step 5: Commit** `fix(undangan): cek kuota dan pembuatan undangan dalam satu transaksi`

---

### Task 9: Undangan yang sudah diisi tidak bisa dihapus

**Files:**
- Modify: `app/Http/Controllers/InvitationController.php` (`destroy`)
- Test: `tests/Feature/InvitationTest.php`

- [ ] **Step 1: Tulis test** undangan parsial (punya 1 `AssessmentResponse`) → DELETE → redirect +
  `error` "Tidak bisa membatalkan undangan yang sudah diisi.", baris tetap ada.
- [ ] **Step 2: Jalankan, pastikan merah**
- [ ] **Step 3: Tambah guard `responses()->exists()` setelah cek `is_completed`**
- [ ] **Step 4: Jalankan `InvitationTest`, pastikan hijau**
- [ ] **Step 5: Commit** `fix(undangan): tolak pembatalan undangan yang sudah berisi jawaban`

---

### Task 10: Satu definisi route `dashboard.index`

**Files:**
- Modify: `routes/web.php:30-31, 89-96`
- Test: `tests/Feature/DashboardViewTest.php`

- [ ] **Step 1: Tulis test** dosen → 200; admin dan jurusan → 403 pada `route('dashboard.index')`.
- [ ] **Step 2: Jalankan, pastikan merah untuk admin/jurusan**
- [ ] **Step 3: Hapus route duplikat di group `role:dosen`, pindahkan satu definisi ke group itu**
- [ ] **Step 4: Jalankan test baru + `DashboardViewTest` + `AdminUserManagementTest`**
- [ ] **Step 5: Commit** `fix(route): satukan definisi dashboard penilaian untuk role dosen`

---

### Task 11: Verifikasi akhir, merge, dan catatan

- [ ] **Step 1:** `php artisan test` → seluruh suite hijau; `php vendor/bin/pint --test app tests` →
  PASS.
- [ ] **Step 2:** Browser: login dosen, buka `/dashboard-penilaian`; login jurusan, buka
  `/jurusan/verifikasi/{id admin}` → 404; login admin, buka `/dashboard-penilaian` → 403; cetak PDF
  setelah menghapus baris pengaturan (lalu pulihkan).
- [ ] **Step 3:** Tulis `specs/2026-10-30-penutupan-risiko-lanjutan.md` berisi apa yang berubah,
  bukti test, dan sisa catatan operator (`lockForUpdate` no-op di SQLite, unique index
  `assessment_responses` menunggu dedupe produksi).
- [ ] **Step 4:** Merge `--no-ff` ke `main`, minta izin sebelum push.

## Definition of Done

- Sebelas risiko tertutup atau tercatat eksplisit sebagai catatan operator.
- `ScoringCharacterizationTest` tidak berubah dan tetap hijau.
- Suite penuh hijau, Pint PASS, tidak ada dependency baru.

---

## Hasil pelaksanaan

Branch `fix/sisa-risiko-lanjutan`, sepuluh commit (Task 2 dan Task 3 digabung karena sama-sama
menyentuh `AssessmentController@store`).

| Risiko | Perubahan | Bukti |
| --- | --- | --- |
| Endpoint token tanpa batas laju | `throttle:60,1` (GET) dan `throttle:20,1` (POST) di `/nilai/{token}` | `AssessmentThrottleTest`; curl nyata 16×302 lalu 429 |
| `abort(403)` di dalam service | `AssessmentCompletedException`, diterjemahkan controller jadi redirect + flash `error` | `AssessmentSubmissionTest::test_kirim_ulang_setelah_selesai_ditolak_dengan_pesan` |
| Validasi jalan sebelum token dicari | Token diresolusikan dulu, aturan pindah ke `AssessmentAnswerRules`; `SubmitAssessmentRequest` dihapus | `AssessmentSubmissionTest::test_token_tidak_dikenal_tetap_404_walau_payload_tidak_valid` |
| Jawaban duplikat menggandakan bobot | `dimensionScores()` memakai subquery `MAX(id)` per `(request, pertanyaan)` | `AssessmentScoreDuplicateTest`; `ScoringCharacterizationTest` tetap hijau |
| Query portofolio terbuang di halaman hasil | `viewDataFor()` memakai `dimensionScores()` langsung | `AssessmentResultTest::test_data_hasil_tidak_mengambil_portofolio` |
| Kop PDF kosong | `UniversitySetting::current()` dengan fallback, dipakai kedua controller PDF | `AssessmentResultTest::test_pdf_tetap_tercetak_saat_pengaturan_kampus_kosong` |
| IDOR `{userId}` / `{portfolioId}` | `dosenOrFail()` dan `whereHas('user', role dosen)` → 404 | `JurusanAuthorizationTest` |
| Kuota undangan non-atomik | Cek kuota + insert dalam `DB::transaction` dengan `lockForUpdate` baris user; email setelah commit | `InvitationTest::test_undangan_di_luar_kuota_tidak_menyisakan_baris` |
| Cascade hapus undangan | Tolak `destroy` bila `responses()->exists()` | `InvitationTest::test_tidak_bisa_membatalkan_undangan_yang_sudah_berisi_jawaban` |
| `dashboard.index` ganda | Satu definisi di group `role:dosen` | `DashboardViewTest::test_dashboard_penilaian_hanya_untuk_dosen` |

Verifikasi: `php artisan test` → 170 passed (796 assertions); `php vendor/bin/pint --test app tests`
→ PASS 79 files.

Pemeriksaan browser (server lokal port 8090, data uji dihapus setelahnya): admin
`/dashboard-penilaian` → 403; jurusan membuka `{userId}` admin → 404; jurusan membuka `{userId}`
dosen → 200; dosen `/dashboard-penilaian` → 200.

Penyimpangan dari rencana:

- Task 3 dikerjakan sebelum Task 1 karena test throttle mengandalkan token tak dikenal
  menghasilkan 404 (perilaku yang baru benar setelah Task 3).
- Test PDF ditaruh di `AssessmentResultTest`, bukan `UniversitySettingTest`, karena fixture
  analisis sudah tersedia di sana.
- Assertion skor portofolio di `JurusanAuthorizationTest` memakai `assertEquals(0, ...)`: kolom
  `score` punya default 0, bukan null.

## Tindak lanjut review independen

Review fresh-context atas seluruh branch menemukan tiga hal yang langsung dikerjakan:

- **Kuota throttle GET/POST berbagi satu penghitung.** Throttle bawaan memakai kunci `domain|ip`
  untuk tamu, jadi 20 kali membuka form menghabiskan kuota kirim jawaban, dan penilai di balik satu
  IP kampus saling memblokir. Sekarang memakai named limiter: 60/menit per token+IP untuk membuka
  form, 20/menit per token+IP untuk kirim jawaban, plus 120/menit per IP agar tebak-token beruntun
  tetap tertahan. Tiga test baru terbukti merah pada konfigurasi lama.
- **Pembatalan undangan masih check-then-act.** Cek `is_completed` dan `responses()->exists()` lalu
  `delete()` bisa menghapus jawaban yang masuk di antara keduanya. Pembatalan sekarang memakai
  transaksi dan `lockForUpdate` pada baris yang sama dengan jalur submit.
- **Empat route penilaian masih hanya `auth`.** Undang penilai, unggah portofolio, proses hasil AI,
  dan halaman hasil dipakai admin/jurusan juga; proses AI membakar kuota Gemini dan portofolio
  non-dosen tidak mungkin dinilai (404). Keempatnya dipindah ke group `role:dosen`.

Catatan operator (tidak diselesaikan di kode):

- `lockForUpdate()` no-op di SQLite. Test berjalan di SQLite in-memory, jadi atomicity kuota hanya
  berlaku di MySQL/MariaDB produksi. Bukti perilaku hanya lewat review kode + test "ditolak tidak
  menyisakan baris" (test itu juga lulus tanpa transaksi, jadi bukan bukti race).
- Unique index `(assessment_request_id, master_question_id)` pada `assessment_responses` belum
  ditambahkan: butuh dedupe data produksi lebih dulu. Skor sekarang aman karena memakai baris
  terbaru, tetapi penggandaan baris masih mungkin terjadi lewat jalur tulis lain.
- Subquery `MAX(id)` pada `dimensionScores()` belum difilter per request pengguna dan belum ada
  index `(assessment_request_id, master_question_id)`. Cukup untuk ukuran data sekarang, tapi
  sebaiknya dioptimalkan bersama penambahan unique index di atas.
- Perubahan perilaku yang disetujui: kirim ulang token selesai tidak lagi 403 melainkan redirect
  dengan pesan; undangan yang sudah berisi jawaban tidak bisa dibatalkan; admin/jurusan yang
  membuka `/dashboard-penilaian` serta empat route penilaian lain mendapat 403 (sebelumnya
  bergantung urutan definisi route atau lolos begitu saja).
