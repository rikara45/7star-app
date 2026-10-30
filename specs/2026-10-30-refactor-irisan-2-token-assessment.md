# Rencana Irisan 2 — Penilaian via Token

Tanggal: 2026-10-30
Induk: `specs/2026-10-30-refactor-arsitektur-design.md` (bagian 7, irisan 2)
Branch: `refactor/assessment-token`

## Tujuan

Menutup tiga cacat pada alur penilaian publik (`/nilai/{token}`):

1. `completed_at` tidak ada di skema, jadi penulisan waktu selesai dibuang diam-diam oleh
   Eloquent. Telah diverifikasi di repo ini: Laravel 12 hanya mengizinkan mass assignment untuk
   kolom yang benar-benar ada (`GuardsAttributes::isGuardableColumn()`), sehingga
   `update(['completed_at' => now()])` hanya menulis `is_completed`.
2. Setiap pengiriman langsung menandai `is_completed = true`, walau jawaban belum lengkap. Satu
   kiriman terpotong (atau klik "Ya, Kirim Sekarang" pada modal konfirmasi, yang memanggil
   `form.submit()` sehingga mem-bypass validasi HTML) mengunci token selamanya dan memasukkan
   rata-rata skor dari jawaban parsial ke hitungan 360.
3. `AssessmentResponse::create()` per jawaban, bukan `updateOrCreate`, sehingga pengiriman ulang
   menggandakan baris dan menggeser rata-rata dimensi.

Hasil akhir: token bisa dilanjutkan, baru terkunci saat semua pertanyaan terjawab, dan pengiriman
ulang memperbaiki jawaban alih-alih menggandakannya.

## Yang tidak berubah

- Nama route `assessment.show`, `assessment.store`, `assessment.thankyou`, `assessment.completed`
  dan URL `/nilai/{token}` (tautan di email penilai tetap valid).
- Skala jawaban 1–5 dan tampilan form (kecuali banner pesan/error dan penandaan jawaban tersimpan).
- `AssessmentScoreService` tidak disentuh; filter `is_completed = true` tetap sama.

## Di luar cakupan (sengaja)

- Unique index `(assessment_request_id, master_question_id)` pada `assessment_responses`. Logika
  `updateOrCreate` sudah menutup penggandaan berurutan; indeks unik menuntut dedupe data produksi
  lebih dulu dan menyentuh tabel yang dipakai test karakterisasi. Dicatat sebagai tugas terpisah.
- Menambah kolom `is_active` pada `master_questions` (keputusan desain: validasi memakai seluruh
  baris).
- Menambah tombol "Simpan sementara". Jalur parsial sudah bisa dicapai lewat modal konfirmasi yang
  memanggil `form.submit()`.
- Mengubah `resources/views/assessment/completed.blade.php` dan `thankyou.blade.php`.

## Struktur yang dipakai

```
database/migrations/2026_10_30_000001_add_completed_at_to_assessment_requests_table.php
app/Http/Requests/SubmitAssessmentRequest.php   # validasi token & jawaban
app/Services/AssessmentSubmissionService.php    # simpan + tentukan selesai
app/Http/Controllers/AssessmentController.php    # tipis: show, store
resources/views/assessment/form.blade.php        # banner + prefill
tests/Feature/AssessmentSubmissionTest.php
```

---

## Task 0 — Siapkan branch

```powershell
git switch main
git switch -c refactor/assessment-token
```

Kriteria: `git status` bersih kecuali `AGENTS.md`, `.opencode/`, `logs/`.

---

## Task 1 — Kolom `completed_at` dan backfill

**Test baru:** `tests/Feature/AssessmentSubmissionTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\AssessmentPeriod;
use App\Models\AssessmentRequest;
use App\Models\MasterQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AssessmentSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_kiriman_lengkap_mengisi_completed_at(): void
    {
        [$request, $questions] = $this->tokenWithQuestions(3);

        $this->post(route('assessment.store', $request->access_token), [
            'answers' => $questions->pluck('id')->mapWithKeys(fn ($id) => [$id => 4])->all(),
        ])->assertRedirect(route('assessment.thankyou'));

        $fresh = $request->fresh();

        $this->assertTrue($fresh->is_completed);
        $this->assertNotNull($fresh->completed_at);
    }

    private function tokenWithQuestions(int $count): array
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $period = AssessmentPeriod::forceCreate(['name' => 'Periode Uji', 'is_active' => true]);

        $request = AssessmentRequest::forceCreate([
            'user_id' => $dosen->id,
            'assessment_period_id' => $period->id,
            'assessor_name' => 'Atasan Uji',
            'assessor_email' => 'atasan@example.test',
            'relationship' => 'superior',
            'access_token' => Str::random(40),
            'is_completed' => false,
        ]);

        $questions = collect(range(1, $count))->map(fn (int $i) => MasterQuestion::forceCreate([
            'dimension' => 'Profesionalisme',
            'statement_self' => 'Pernyataan self '.$i,
            'statement_other' => 'Pernyataan other '.$i,
        ]));

        return [$request, $questions];
    }
}
```

Test ini **merah** hari ini: `completed_at` null karena bukan kolom (terbukti lewat pemeriksaan
`getQueryLog`, hanya `is_completed` yang ikut di `UPDATE`).

**File baru:** `database/migrations/2026_10_30_000001_add_completed_at_to_assessment_requests_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('assessment_requests', 'completed_at')) {
            Schema::table('assessment_requests', function (Blueprint $table) {
                $table->timestamp('completed_at')->nullable()->after('is_completed');
            });
        }

        // Baris yang sudah selesai sebelum kolom ini ada memakai updated_at sebagai perkiraan.
        DB::table('assessment_requests')
            ->where('is_completed', true)
            ->whereNull('completed_at')
            ->update(['completed_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('assessment_requests', 'completed_at')) {
            Schema::table('assessment_requests', function (Blueprint $table) {
                $table->dropColumn('completed_at');
            });
        }
    }
};
```

**File diubah:** `app/Models/AssessmentRequest.php` — tambahkan cast:

```php
protected function casts(): array
{
    return [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];
}
```

Cast eksplisit juga membuat `completed_at` dianggap kolom yang bisa diisi massal tanpa bergantung
pada introspeksi skema.

**Verifikasi:**

```powershell
php artisan test --filter=AssessmentSubmissionTest
php artisan migrate
php artisan tinker --execute="echo App\Models\AssessmentRequest::where('is_completed', true)->whereNull('completed_at')->count();"
php vendor/bin/pint app/Models database/migrations tests/Feature/AssessmentSubmissionTest.php
```

Tinker harus mencetak `0` di database lokal (artinya backfill mengisi baris lama). Laporkan bila
lokal tidak punya baris selesai.

**Commit:** `fix(penilaian): tambah kolom completed_at dan backfill data lama`

---

## Task 2 — Validasi pengiriman

**Test ditambahkan ke** `tests/Feature/AssessmentSubmissionTest.php`:

```php
public function test_id_pertanyaan_palsu_ditolak_422(): void
{
    [$request] = $this->tokenWithQuestions(2);

    $this->post(route('assessment.store', $request->access_token), [
        'answers' => [999999 => 4],
    ])->assertStatus(422);

    $this->assertDatabaseCount('assessment_responses', 0);
}

public function test_jawaban_kosong_ditolak_422(): void
{
    [$request] = $this->tokenWithQuestions(2);

    $this->post(route('assessment.store', $request->access_token), [
        'answers' => [],
    ])->assertSessionHasErrors('answers');
}

public function test_skor_di_luar_1_sampai_5_ditolak_422(): void
{
    [$request, $questions] = $this->tokenWithQuestions(2);

    $this->post(route('assessment.store', $request->access_token), [
        'answers' => [$questions[0]->id => 0, $questions[1]->id => 6],
    ])->assertSessionHasErrors([
        'answers.'.$questions[0]->id,
        'answers.'.$questions[1]->id,
    ]);
}
```

Nama key error mengikuti bentuk `answers.<id>`, bukan urutan array. Token tidak dikenal tetap 404
lewat `firstOrFail` (perilaku lama, tidak perlu test baru).

**File baru:** `app/Http/Requests/SubmitAssessmentRequest.php`

```php
<?php

namespace App\Http\Requests;

use App\Models\MasterQuestion;
use Illuminate\Foundation\Http\FormRequest;

class SubmitAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route publik dengan token
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'answers' => ['required', 'array', 'min:1', $this->knownQuestionIds()],
            'answers.*' => ['required', 'integer', 'between:1,5'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'answers.required' => 'Belum ada jawaban yang dikirim.',
            'answers.array' => 'Format jawaban tidak dikenali.',
            'answers.min' => 'Isi minimal satu jawaban.',
            'answers.*.required' => 'Setiap pertanyaan wajib diberi skor.',
            'answers.*.integer' => 'Skor harus berupa angka bulat.',
            'answers.*.between' => 'Skor harus antara 1 sampai 5.',
        ];
    }

    /**
     * Tolak ID pertanyaan yang tidak ada di master_questions dengan 422, bukan 500 dari foreign key.
     */
    private function knownQuestionIds(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            $known = MasterQuestion::pluck('id')->all();
            $unknown = array_diff(array_map('intval', array_keys((array) $value)), $known);

            if ($unknown !== []) {
                $fail('Ada pertanyaan yang tidak dikenal dalam jawaban.');
            }
        };
    }
}
```

**File diubah:** `app/Http/Controllers/AssessmentController.php` — `store()` memakai
`SubmitAssessmentRequest` (perilaku penyimpanan belum diubah di task ini, hanya validasi).

**Verifikasi:**

```powershell
php artisan test --filter=AssessmentSubmissionTest
```

**Commit:** `fix(penilaian): validasi id pertanyaan dan rentang skor`

---

## Task 3 — Service pengiriman: parsial, lengkap, kirim ulang

**Test ditambahkan ke** `tests/Feature/AssessmentSubmissionTest.php`:

```php
public function test_kiriman_parsial_tersimpan_tanpa_menandai_selesai(): void
{
    [$request, $questions] = $this->tokenWithQuestions(3);

    $this->post(route('assessment.store', $request->access_token), [
        'answers' => [$questions[0]->id => 5, $questions[1]->id => 3],
    ])->assertRedirect(route('assessment.show', $request->access_token))
        ->assertSessionHas('status', 'Jawaban tersimpan (2 dari 3). Masih ada 1 pertanyaan.');

    $fresh = $request->fresh();

    $this->assertFalse($fresh->is_completed);
    $this->assertNull($fresh->completed_at);
    $this->assertSame(2, $fresh->responses()->count());
}

public function test_melanjutkan_kiriman_parsial_tidak_menggandakan_jawaban(): void
{
    [$request, $questions] = $this->tokenWithQuestions(3);

    $this->post(route('assessment.store', $request->access_token), [
        'answers' => [$questions[0]->id => 5],
    ]);

    $this->post(route('assessment.store', $request->access_token), [
        'answers' => [
            $questions[0]->id => 2, // diperbaiki
            $questions[1]->id => 4,
            $questions[2]->id => 4,
        ],
    ])->assertRedirect(route('assessment.thankyou'));

    $fresh = $request->fresh();

    $this->assertTrue($fresh->is_completed);
    $this->assertNotNull($fresh->completed_at);
    $this->assertSame(3, $fresh->responses()->count());
    $this->assertDatabaseHas('assessment_responses', [
        'assessment_request_id' => $request->id,
        'master_question_id' => $questions[0]->id,
        'score' => 2,
    ]);
}

public function test_kirim_ulang_setelah_selesai_ditolak_403(): void
{
    [$request, $questions] = $this->tokenWithQuestions(1);

    $this->post(route('assessment.store', $request->access_token), [
        'answers' => [$questions[0]->id => 4],
    ]);

    $this->post(route('assessment.store', $request->access_token), [
        'answers' => [$questions[0]->id => 1],
    ])->assertForbidden();

    $this->assertDatabaseHas('assessment_responses', [
        'assessment_request_id' => $request->id,
        'score' => 4,
    ]);
}
```

**File baru:** `app/Services/AssessmentSubmissionService.php`

```php
<?php

namespace App\Services;

use App\Models\AssessmentRequest;
use App\Models\AssessmentResponse;
use App\Models\MasterQuestion;
use Illuminate\Support\Facades\DB;

class AssessmentSubmissionService
{
    /**
     * Simpan jawaban; token hanya ditandai selesai bila semua pertanyaan terjawab.
     *
     * @param  array<int|string, int>  $answers
     * @return array{total: int, answered: int, remaining: int, completed: bool}
     */
    public function submit(AssessmentRequest $assessmentRequest, array $answers): array
    {
        if ($assessmentRequest->is_completed) {
            abort(403, 'Penilaian ini sudah diselesaikan sebelumnya.');
        }

        return DB::transaction(function () use ($assessmentRequest, $answers) {
            foreach ($answers as $questionId => $score) {
                AssessmentResponse::updateOrCreate(
                    [
                        'assessment_request_id' => $assessmentRequest->id,
                        'master_question_id' => $questionId,
                    ],
                    ['score' => $score]
                );
            }

            $total = MasterQuestion::count();
            $answered = $assessmentRequest->responses()->count();
            $completed = $total > 0 && $answered >= $total;

            if ($completed) {
                $assessmentRequest->update([
                    'is_completed' => true,
                    'completed_at' => now(),
                ]);
            }

            return [
                'total' => $total,
                'answered' => $answered,
                'remaining' => max(0, $total - $answered),
                'completed' => $completed,
            ];
        });
    }
}
```

**File diubah:** `app/Http/Controllers/AssessmentController.php`

```php
public function __construct(private AssessmentSubmissionService $submissions) {}

public function show($token)
{
    $assessmentRequest = AssessmentRequest::with('user')
        ->where('access_token', $token)
        ->firstOrFail();

    if ($assessmentRequest->is_completed) {
        return view('assessment.completed');
    }

    $groupedQuestions = MasterQuestion::all()->groupBy('dimension');
    $savedAnswers = $assessmentRequest->responses()->pluck('score', 'master_question_id');

    return view('assessment.form', compact('assessmentRequest', 'groupedQuestions', 'token', 'savedAnswers'));
}

public function store(SubmitAssessmentRequest $request, $token)
{
    $assessmentRequest = AssessmentRequest::where('access_token', $token)->firstOrFail();

    $result = $this->submissions->submit($assessmentRequest, $request->validated('answers'));

    if ($result['completed']) {
        return redirect()->route('assessment.thankyou');
    }

    return redirect()
        ->route('assessment.show', $token)
        ->with('status', sprintf(
            'Jawaban tersimpan (%d dari %d). Masih ada %d pertanyaan.',
            $result['answered'],
            $result['total'],
            $result['remaining']
        ));
}
```

**Verifikasi:**

```powershell
php artisan test --filter=AssessmentSubmissionTest
php artisan test
```

**Commit:** `fix(penilaian): simpan jawaban bertahap dan kunci token saat lengkap`

---

## Task 4 — Form: pesan status, error, dan jawaban tersimpan

**Test ditambahkan ke** `tests/Feature/AssessmentSubmissionTest.php`:

```php
public function test_form_menampilkan_jawaban_tersimpan_terpilih(): void
{
    [$request, $questions] = $this->tokenWithQuestions(2);

    $this->post(route('assessment.store', $request->access_token), [
        'answers' => [$questions[0]->id => 5],
    ]);

    $this->get(route('assessment.show', $request->access_token))
        ->assertOk()
        ->assertSee('Jawaban tersimpan (1 dari 2). Masih ada 1 pertanyaan.')
        ->assertSee('name="answers['.$questions[0]->id.']" value="5" class="peer sr-only" required checked', false);
}

public function test_form_menampilkan_error_validasi(): void
{
    [$request] = $this->tokenWithQuestions(2);

    $this->from(route('assessment.show', $request->access_token))
        ->post(route('assessment.store', $request->access_token), ['answers' => []])
        ->assertSessionHasErrors('answers');

    $this->get(route('assessment.show', $request->access_token))
        ->assertOk()
        ->assertSee('Isi minimal satu jawaban.');
}
```

**File diubah:** `resources/views/assessment/form.blade.php`

- Setelah blok judul (sebelum `<form>`), tambahkan banner status dan banner error:

```blade
@if(session('status'))
    <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl px-5 py-4 mb-6 text-sm font-medium">
        {{ session('status') }}
    </div>
@endif

@if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-5 py-4 mb-6">
        <ul class="list-disc list-inside text-sm space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
```

- Pada radio: tambahkan `@checked(...)` di akhir tag input, sehingga hasil render menjadi
  `... class="peer sr-only" required checked>`:

```blade
<input type="radio" name="answers[{{ $q->id }}]" value="{{ $i }}" class="peer sr-only" required @checked(($savedAnswers[$q->id] ?? null) == $i)>
```

**Verifikasi:**

```powershell
php artisan test --filter=AssessmentSubmissionTest
php artisan test
php vendor/bin/pint --test app tests
```

**Commit:** `fix(penilaian): tampilkan status simpan dan jawaban tersimpan di form`

---

## Task 5 — Verifikasi akhir dan merge

```powershell
php artisan migrate --pretend
php artisan test
php vendor/bin/pint --test app tests
```

Manual (laporkan apa adanya bila tidak dijalankan):

1. Login sebagai dosen, undang satu penilai, buka tautan `/nilai/{token}` di browser.
2. Isi 2 pertanyaan, klik "Kirim Penilaian" lalu "Ya, Kirim Sekarang" (modal mem-bypass validasi
   HTML) — harus kembali ke form dengan pesan "Jawaban tersimpan (2 dari N)" dan pilihan tadi tetap
   terpilih.
3. Lengkapi sisanya, kirim — harus masuk halaman terima kasih; buka ulang tautan — halaman "sudah
   selesai".
4. Cek DB: `assessment_responses` tidak punya baris ganda per `(assessment_request_id,
   master_question_id)`, dan `completed_at` terisi.

Merge setelah hijau:

```powershell
git switch main
git merge --no-ff refactor/assessment-token
```

Push hanya setelah dikonfirmasi pemilik repo.

## Definition of done

- `AssessmentController` hanya memuat `show`, `store`, `thankyou`, `completed` (± 60 baris) tanpa
  logika penyimpanan.
- Kolom `completed_at` ada, terisi saat lengkap, dan backfill mengisi baris lama.
- Token parsial tetap terbuka; token lengkap menolak kiriman berikutnya dengan 403.
- Tidak ada jawaban ganda setelah pengiriman berulang.
- Test baru: 9 feature, hijau bersama 64 test lama; test karakterisasi tidak diubah.
- Pint PASS untuk `app` dan `tests`.
