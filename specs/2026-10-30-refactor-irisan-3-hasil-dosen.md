# Rencana Irisan 3 — Skor dan Hasil (Dosen)

> **Untuk pelaksana:** gunakan skill `superpowers:executing-plans` untuk mengeksekusi rencana ini
> task demi task (eksekusi inline di sesi ini, sesuai pilihan pemilik repo).

**Tujuan:** memindahkan seluruh logika hasil dosen (`result.generate`, `result.show`,
`result.pdf`) dari `UserDashboardController` ke satu service + satu controller domain, tanpa
mengubah angka, pesan, atau nama route.

**Arsitektur:** `AssessmentResultService` menjadi satu-satunya jalur penyusunan data hasil
(analisis tersimpan + rincian dimensi realtime) dan orkestrasi narasi AI. `AssessmentResultController`
tipis: resolusi periode, panggil service, kembalikan redirect/view/PDF. `UserDashboardController`
tinggal dashboard + undangan.

**Stack:** Laravel 12.39, Blade, DomPDF, PHPUnit feature test, SQLite in-memory untuk test.

**Spec:** `specs/2026-10-30-refactor-arsitektur-design.md` (bagian "Irisan 3"),
`specs/2026-10-30-refactor-irisan-2-token-assessment.md` (pola yang sama).

## Batasan Global

- Tidak menambah dependency. Tidak memakai queue worker (target shared hosting).
- Nama route `result.generate`, `result.show`, `result.pdf` dan URL-nya tidak berubah.
- `AssessmentScoreService` dan `AiNarrativeService` tidak diubah.
- `tests/Feature/ScoringCharacterizationTest.php` tidak boleh diubah sama sekali.
- Prompt Gemini dipindah apa adanya (termasuk spasi dan kalimatnya).
- Analisis tersimpan untuk tampilan tetap diambil `latest()` lintas periode; pengecekan "skor
  berubah" pada proses generate tetap difilter per periode aktif.
- Pesan error yang sudah ada dipertahankan bunyinya.
- `php artisan test` hijau dan `php vendor/bin/pint --test app tests` PASS setiap task.

## Fokus Review

- Dosen menekan "Proses" saat skor belum berubah → tidak ada panggilan AI baru, tidak ada baris
  `ai_analyses` tambahan, tetap diarahkan ke halaman hasil.
- Gemini gagal/timeout saat proses → skor dan analisis tetap tersimpan dengan narasi fallback,
  bukan error 500.
- Dosen membuka halaman hasil tanpa pernah memproses → redirect ke dashboard dengan pesan, bukan 500.
- Halaman hasil dan PDF memakai rincian dimensi yang identik untuk data yang sama.
- Analisis tersimpan berasal dari periode lain (periode aktif berganti) → halaman hasil tetap
  memakai analisis terbaru tanpa error.

Di luar cakupan (dicatat, bukan dikerjakan di sini): PDF tetap 500 bila tabel
`university_settings` kosong (ditangani di irisan 6 bersama pengaturan universitas); batas jumlah
kategori portofolio pada prompt tidak berubah.

---

### Task 0: Siapkan branch

- [ ] **Step 1: Buat branch dari `main`**

```powershell
git switch main
git switch -c refactor/result-dosen
```

Kriteria: `git status --short` hanya memuat `AGENTS.md`, `.opencode/`, `logs/`.

---

### Task 1: `AssessmentResultService`

**Files:**
- Create: `app/Services/AssessmentResultService.php`
- Test: `tests/Feature/AssessmentResultTest.php`

**Interfaces:**
- Consumes: `AssessmentScoreService::calculate(User, AssessmentPeriod): array` (kunci
  `dimensionDetails`, `behaviorScore100`, `portfolioScore`, `finalScore`, `stars`, `portfolios`,
  `dimensionString`), `AiNarrativeService::generate(string): string`,
  `AiAnalysis` (fillable: `user_id`, `assessment_period_id`, `score_behavior`, `score_portfolio`,
  `final_score`, `star_rating`, `ai_narrative`), `PortfolioCategories::all(): array<string,string>`.
- Produces:
  - `latestAnalysis(User $user): ?AiAnalysis` — `latest()` lintas periode.
  - `viewDataFor(User $user, AssessmentPeriod $period): ?array{analysis: AiAnalysis, dimensionDetails: array<string, float>}`
  - `generateFor(User $user, AssessmentPeriod $period): void`

- [ ] **Step 1: Tulis test yang gagal**

```php
<?php

namespace Tests\Feature;

use App\Models\AiAnalysis;
use App\Models\AssessmentPeriod;
use App\Models\AssessmentRequest;
use App\Models\AssessmentResponse;
use App\Models\MasterQuestion;
use App\Models\UniversitySetting;
use App\Models\User;
use App\Services\AssessmentResultService;
use App\Services\AssessmentScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class AssessmentResultTest extends TestCase
{
    use RefreshDatabase;

    public function test_data_hasil_memakai_rincian_dimensi_dari_score_service(): void
    {
        [$user, $period] = $this->dosenWithAnalysis();

        $expected = app(AssessmentScoreService::class)->dimensionScores($user->id, $period);
        $data = app(AssessmentResultService::class)->viewDataFor($user, $period);

        $this->assertNotNull($data);
        $this->assertSame($expected, $data['dimensionDetails']);
    }

    public function test_data_hasil_null_bila_belum_ada_analisis(): void
    {
        [$user] = $this->dosenWithAnalysis(analysis: false);

        $this->assertNull(app(AssessmentResultService::class)->viewDataFor($user, $this->period()));
    }

    public function test_analisis_lintas_periode_tetap_memakai_yang_terbaru(): void
    {
        [$user, $period] = $this->dosenWithAnalysis();

        $lain = AssessmentPeriod::forceCreate(['name' => 'Periode Lain', 'is_active' => false]);
        AiAnalysis::forceCreate([
            'user_id' => $user->id,
            'assessment_period_id' => $lain->id,
            'score_behavior' => 10,
            'score_portfolio' => 10,
            'final_score' => 10,
            'star_rating' => '1 Bintang',
            'ai_narrative' => 'Narasi periode lain.',
        ]);

        $data = app(AssessmentResultService::class)->viewDataFor($user, $period);

        $this->assertSame('Narasi periode lain.', $data['analysis']->ai_narrative);
    }

    public function test_proses_hasil_menyimpan_analisis_lewat_service(): void
    {
        Http::fake(['*' => Http::response($this->geminiResponse('Narasi uji.'))]);

        [$user, $period] = $this->dosenWithScores([5, 4]);

        $this->actingAs($user)->post(route('result.generate'))->assertRedirect(route('result.show'));

        $this->assertDatabaseHas('ai_analyses', [
            'user_id' => $user->id,
            'assessment_period_id' => $period->id,
            'star_rating' => '2 Bintang',
        ]);
        $this->assertEqualsWithDelta(90.0, (float) AiAnalysis::first()->score_behavior, 0.001);
    }

    public function test_proses_hasil_tidak_memanggil_ai_bila_skor_tidak_berubah(): void
    {
        Http::fake();
        [$user, $period] = $this->dosenWithAnalysis();

        $this->actingAs($user)->post(route('result.generate'))->assertRedirect(route('result.show'));

        Http::assertNothingSent();
        $this->assertSame(1, AiAnalysis::where('user_id', $user->id)->count());
    }

    public function test_proses_hasil_tetap_menyimpan_skor_saat_ai_gagal(): void
    {
        Http::fake(['*' => Http::response('', 500)]);

        [$user, $period] = $this->dosenWithScores([5]);

        $this->actingAs($user)->post(route('result.generate'))->assertRedirect(route('result.show'));

        $analysis = AiAnalysis::firstOrFail();

        $this->assertEqualsWithDelta(100.0, (float) $analysis->score_behavior, 0.001);
        $this->assertStringContainsString('belum tersedia', $analysis->ai_narrative);
    }

    private function dosenWithAnalysis(bool $analysis = true): array
    {
        [$user, $period] = $this->dosenWithScores([5, 4]);

        if ($analysis) {
            AiAnalysis::forceCreate([
                'user_id' => $user->id,
                'assessment_period_id' => $period->id,
                'score_behavior' => 90,
                'score_portfolio' => 0,
                'final_score' => 63,
                'star_rating' => '2 Bintang',
                'ai_narrative' => 'Narasi tersimpan.',
            ]);
        }

        return [$user, $period];
    }

    private function dosenWithScores(array $scores): array
    {
        $user = User::factory()->create(['role' => 'dosen']);
        $period = $this->period();

        $request = AssessmentRequest::forceCreate([
            'user_id' => $user->id,
            'assessment_period_id' => $period->id,
            'assessor_name' => 'Atasan Uji',
            'assessor_email' => 'atasan@example.test',
            'relationship' => 'superior',
            'access_token' => Str::random(40),
            'is_completed' => true,
        ]);

        foreach ($scores as $score) {
            $question = MasterQuestion::forceCreate([
                'dimension' => 'Profesionalisme',
                'statement_self' => 'Pernyataan self',
                'statement_other' => 'Pernyataan other',
            ]);

            AssessmentResponse::forceCreate([
                'assessment_request_id' => $request->id,
                'master_question_id' => $question->id,
                'score' => $score,
            ]);
        }

        return [$user, $period];
    }

    private function period(): AssessmentPeriod
    {
        return AssessmentPeriod::forceCreate(['name' => 'Periode Uji', 'is_active' => true]);
    }

    private function geminiResponse(string $text): array
    {
        return ['candidates' => [['content' => ['parts' => [['text' => $text]]]]]];
    }
}
```

- [ ] **Step 2: Jalankan test, pastikan gagal**

```powershell
php artisan test --filter=AssessmentResultTest
```

Harapan: gagal karena `App\Services\AssessmentResultService` belum ada.

- [ ] **Step 3: Buat `app/Services/AssessmentResultService.php`**

Signature dan tanggung jawab:

```php
namespace App\Services;

class AssessmentResultService
{
    public function __construct(
        private AssessmentScoreService $scoreService,
        private AiNarrativeService $narrativeService,
    ) {}

    public function latestAnalysis(User $user): ?AiAnalysis
    // AiAnalysis::where('user_id', $user->id)->latest()->first()

    /** @return array{analysis: AiAnalysis, dimensionDetails: array<string, float>}|null */
    public function viewDataFor(User $user, AssessmentPeriod $period): ?array
    // latestAnalysis() null → null.
    // Selain itu: analysis + $this->scoreService->calculate($user, $period)['dimensionDetails'].

    public function generateFor(User $user, AssessmentPeriod $period): void
}
```

`generateFor()` memindahkan isi `UserDashboardController::generateResult()` baris 194–257 apa
adanya, dengan penyesuaian: `$this->scoreService`, `$this->narrativeService`, prompt dan
`PortfolioCategories` tetap sama; pengecekan "tidak berubah" memakai `AiAnalysis` yang difilter
`assessment_period_id = $period->id` + `latest()`; `updateOrCreate` tetap memakai kunci
`user_id` + `assessment_period_id`.

- [ ] **Step 4: Jalankan test, pastikan lulus**

```powershell
php artisan test --filter=AssessmentResultTest
php vendor/bin/pint app/Services tests/Feature/AssessmentResultTest.php
```

- [ ] **Step 5: Commit**

```powershell
git add app/Services/AssessmentResultService.php tests/Feature/AssessmentResultTest.php
git commit -m "refactor(hasil): satukan penyusunan hasil dosen dalam satu service"
```

---

### Task 2: `AssessmentResultController` dan pemangkasan `UserDashboardController`

**Files:**
- Create: `app/Http/Controllers/AssessmentResultController.php`
- Modify: `app/Http/Controllers/UserDashboardController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/AssessmentResultTest.php`

**Interfaces:**
- Consumes: `AssessmentResultService` dari Task 1.
- Produces: route name `result.generate`, `result.show`, `result.pdf` tetap, kini menunjuk
  `AssessmentResultController@generate/show/pdf`.

- [ ] **Step 1: Tambah test yang gagal**

Tambahkan ke `tests/Feature/AssessmentResultTest.php`:

```php
public function test_halaman_hasil_menampilkan_rincian_dimensi(): void
{
    [$user] = $this->dosenWithAnalysis();

    $this->actingAs($user)
        ->get(route('result.show'))
        ->assertOk()
        ->assertSee('Profesionalisme')
        ->assertSee('4.50');
}

public function test_halaman_hasil_mengalihkan_bila_belum_ada_analisis(): void
{
    [$user] = $this->dosenWithAnalysis(analysis: false);

    $this->actingAs($user)
        ->get(route('result.show'))
        ->assertRedirect(route('dashboard.index'))
        ->assertSessionHas('error', 'Belum ada hasil. Silakan proses dulu.');
}

public function test_pdf_dosen_terunduh_dengan_data_yang_sama(): void
{
    [$user] = $this->dosenWithAnalysis();

    UniversitySetting::forceCreate([
        'uni_name' => 'Universitas Uji',
        'uni_address' => 'Jl. Uji',
        'uni_website' => 'https://uji.test',
    ]);

    $response = $this->actingAs($user)->get(route('result.pdf'));

    $response->assertOk();
    $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
}
```

- [ ] **Step 2: Jalankan test, pastikan dua test baru hijau dan satu merah**

```powershell
php artisan test --filter=AssessmentResultTest
```

Harapan: `test_halaman_hasil_mengalihkan_bila_belum_ada_analisis` merah karena controller lama
memakai pesan yang sama tetapi route masih ke `UserDashboardController` (boleh juga sudah hijau);
`test_pdf_dosen_terunduh_dengan_data_yang_sama` merah bila `UniversitySetting` belum terbaca.
Bila semua sudah hijau, lanjut; test ini menjadi jaring pengaman pemindahan controller.

- [ ] **Step 3: Buat `AssessmentResultController`**

```php
class AssessmentResultController extends Controller
{
    public function __construct(private AssessmentResultService $results) {}

    public function generate()     // POST /proses-hasil
    public function show()         // GET  /hasil-penilaian
    public function pdf()          // GET  /hasil-penilaian/pdf
}
```

Perilaku yang harus dipertahankan persis:

- `generate`: tanpa periode aktif → `back()->with('error', 'Tidak ada periode aktif.')`; selain itu
  `$this->results->generateFor($user, $period)` lalu `redirect()->route('result.show')`.
- `show`: tanpa periode → `redirect()->route('dashboard.index')->with('error', 'Tidak ada periode penilaian aktif.')`;
  `viewDataFor()` null → `redirect()->route('dashboard.index')->with('error', 'Belum ada hasil. Silakan proses dulu.')`;
  selain itu `view('result_7stars', ['analysis' => ..., 'dimensionDetails' => ...])`.
- `pdf`: tanpa periode → `back()->with('error', 'Tidak ada periode penilaian aktif.')`;
  tanpa analisis → `back()->with('error', 'Belum ada hasil untuk dicetak.')`; selain itu ambil
  `UniversitySetting::first()`, `Pdf::loadView('pdf.report', compact('user', 'analysis', 'setting', 'dimensionDetails'))`,
  lalu `->download('Laporan_Kepemimpinan_'.$user->name.'.pdf')`.

- [ ] **Step 4: Pindahkan route dan pangkas `UserDashboardController`**

- `routes/web.php`: `result.generate`, `result.show`, `result.pdf` menunjuk
  `AssessmentResultController`.
- `UserDashboardController`: hapus `generateResult`, `showResult`, `exportPdf`, import
  `AiNarrativeService`, `UniversitySetting`, `Pdf`, dan properti `$narrativeService`.
  `index`, `invite`, `destroyRequest` tidak berubah.

- [ ] **Step 5: Verifikasi**

```powershell
php artisan test
php vendor/bin/pint --test app tests
```

Harapan: seluruh suite hijau (76 test) tanpa mengubah `ScoringCharacterizationTest`, Pint PASS.

- [ ] **Step 6: Commit**

```powershell
git add app/Http/Controllers/AssessmentResultController.php app/Http/Controllers/UserDashboardController.php routes/web.php tests/Feature/AssessmentResultTest.php
git commit -m "refactor(hasil): pisahkan controller hasil dosen dari dashboard"
```

---

### Task 3: Verifikasi nyata dan merge

- [ ] **Step 1: Cek statis**

```powershell
php artisan test
php vendor/bin/pint --test app tests
wc -l app/Http/Controllers/UserDashboardController.php app/Http/Controllers/AssessmentResultController.php
php artisan route:list --name=result
```

Harapan: 79 test hijau, Pint PASS, `UserDashboardController` < 180 baris,
`AssessmentResultController` < 90 baris, tiga route `result.*` ada.

- [ ] **Step 2: Verifikasi browser (laporkan apa adanya bila tidak dijalankan)**

1. `php artisan serve --port=8084`, login sebagai dosen yang punya penilaian selesai.
2. Buka `/dashboard-penilaian`, klik "Proses" → diarahkan ke `/hasil-penilaian`, halaman memuat
   bintang, skor, dan rincian dimensi.
3. Unduh PDF → berkas `.pdf` terunduh dan terbuka.
4. Tekan "Proses" lagi tanpa mengubah data → tidak ada panggilan Gemini baru (cek log), tetap di
   halaman hasil.

- [ ] **Step 3: Merge**

```powershell
git switch main
git merge --no-ff refactor/result-dosen
```

Push hanya setelah dikonfirmasi pemilik repo.

## Definition of Done

- `UserDashboardController` hanya berisi `index`, `invite`, `destroyRequest`.
- `AssessmentResultController` hanya berisi `generate`, `show`, `pdf` tanpa perhitungan skor.
- Halaman hasil dan PDF memakai `AssessmentResultService::viewDataFor()` yang sama.
- `ScoringCharacterizationTest` tidak berubah dan tetap hijau.
- Test baru: 9 feature (6 service + 3 route/PDF), suite penuh hijau, Pint PASS.
