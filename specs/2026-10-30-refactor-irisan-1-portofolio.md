# Rencana Irisan 1 — Portofolio dan Unggahan

Tanggal: 2026-10-30
Induk: `specs/2026-10-30-refactor-arsitektur-design.md` (bagian 7, irisan 1)
Branch: `refactor/portfolio-uploads`

## Tujuan

Menutup dua masalah yang saling berkaitan: bukti portofolio (ijazah, sertifikat, SK) saat ini
disimpan di disk publik sehingga siapa pun yang menebak URL bisa mengunduhnya, dan daftar kategori
portofolio hidup di dua tempat (`PortfolioController::$categories` dan `getCategories()`) sehingga
keduanya bisa menyimpang.

Hasil akhir irisan ini: satu sumber kategori, validasi kategori berwhitelist, berkas baru
tersimpan di disk privat, dan unduhan hanya lewat route terotorisasi.

## Yang tidak berubah

- Nama route `portfolio.store` dan URL `POST /upload-portofolio` (dipakai form dashboard).
- Perilaku `updateOrCreate` per `(user_id, category)`: unggah ulang menimpa baris yang sama.
- Status awal `uploaded` dan `score = 0` (menunggu penilaian jurusan).
- Skor, bobot, dan ambang bintang — tidak ada file skor yang disentuh.
- `App\Services\AssessmentScoreService` dan `AiNarrativeService` — tidak disentuh.

## Di luar cakupan (sengaja)

- Memindahkan berkas lama dari `storage/app/public/portfolios` ke disk privat. Itu tugas operator;
  route baru tetap melayani kedua disk.
- Menghapus berkas lama saat dosen mengganti unggahan. Berkas yatim di disk privat dibiarkan
  (tidak berbahaya, dan menghapus berkas publik legacy berisiko).
- Menutup akses `/storage/...` untuk berkas lama. Butuh keputusan operator (hapus symlink atau
  pindahkan berkas).

## Struktur yang dipakai

```
app/Support/PortfolioCategories.php          # satu sumber daftar kategori
app/Http/Requests/StorePortfolioRequest.php  # validasi unggahan
app/Policies/PortfolioPolicy.php             # aturan unduh
app/Http/Controllers/PortfolioController.php # tipis: store + download
tests/Unit/PortfolioCategoriesTest.php
tests/Feature/PortfolioUploadTest.php
tests/Feature/PortfolioDownloadTest.php
```

---

## Task 0 — Siapkan branch

```powershell
git switch main
git switch -c refactor/portfolio-uploads
```

Kriteria: `git status` bersih (kecuali `AGENTS.md`, `.opencode/`, `logs/` yang memang tidak
di-commit).

---

## Task 1 — Satu sumber daftar kategori

**File dibuat:** `app/Support/PortfolioCategories.php`

```php
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
```

**Test baru:** `tests/Unit/PortfolioCategoriesTest.php`

```php
<?php

namespace Tests\Unit;

use App\Support\PortfolioCategories;
use PHPUnit\Framework\TestCase;

class PortfolioCategoriesTest extends TestCase
{
    public function test_daftar_kategori_tetap_sepuluh_dan_berpetunjuk(): void
    {
        $categories = PortfolioCategories::all();

        $this->assertCount(10, $categories);

        foreach ($categories as $name => $hint) {
            $this->assertNotSame('', trim($name));
            $this->assertNotSame('', trim($hint));
        }
    }
}
```

**Verifikasi:**

```powershell
php artisan test --filter=PortfolioCategoriesTest
php vendor/bin/pint app/Support tests/Unit/PortfolioCategoriesTest.php
```

**Commit:** `refactor(portofolio): satukan daftar kategori di satu kelas`

---

## Task 2 — Validasi kategori berwhitelist

Tulis test dulu (harus merah), lalu implementasi.

**Test baru:** `tests/Feature/PortfolioUploadTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\PortfolioCategories;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortfolioUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_kategori_di_luar_daftar_ditolak(): void
    {
        Storage::fake('local');

        $dosen = User::factory()->create(['role' => 'dosen']);

        $this->actingAs($dosen)
            ->post(route('portfolio.store'), [
                'category' => 'Kategori Karangan',
                'file' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('category');

        $this->assertDatabaseCount('portfolios', 0);
        Storage::disk('local')->assertDirectoryEmpty('portfolios');
    }

    public function test_kategori_sah_diterima(): void
    {
        Storage::fake('local');

        $dosen = User::factory()->create(['role' => 'dosen']);
        $category = array_key_first(PortfolioCategories::all());

        $this->actingAs($dosen)
            ->post(route('portfolio.store'), [
                'category' => $category,
                'file' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
                'description' => 'Ijazah S3',
            ])
            ->assertRedirect()
            ->assertSessionHas('success_portfolio');

        $this->assertDatabaseHas('portfolios', [
            'user_id' => $dosen->id,
            'category' => $category,
            'description' => 'Ijazah S3',
            'status' => 'uploaded',
            'score' => 0,
        ]);
    }
}
```

**File baru:** `app/Http/Requests/StorePortfolioRequest.php`

```php
<?php

namespace App\Http\Requests;

use App\Support\PortfolioCategories;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePortfolioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route sudah di balik middleware auth
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', 'string', Rule::in(array_keys(PortfolioCategories::all()))],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category.required' => 'Kategori portofolio wajib dipilih.',
            'category.in' => 'Kategori portofolio tidak dikenal.',
            'file.required' => 'Berkas bukti wajib diunggah.',
            'file.mimes' => 'Berkas harus berupa PDF, JPG, JPEG, atau PNG.',
            'file.max' => 'Ukuran berkas maksimal 2 MB.',
            'description.max' => 'Keterangan maksimal 500 karakter.',
        ];
    }
}
```

**File diubah:** `app/Http/Controllers/PortfolioController.php` — `store()` menerima
`StorePortfolioRequest` dan memakai `$request->validated(...)`, bukan `$request->validate(...)`.
Jangan ubah disk penyimpanan di task ini (masih `public`).

**Verifikasi:**

```powershell
php artisan test --filter=PortfolioUploadTest
```

**Commit:** `feat(portofolio): tolak kategori di luar daftar rubrik`

---

## Task 3 — Simpan berkas di disk privat

**Test ditambahkan ke** `tests/Feature/PortfolioUploadTest.php`:

```php
public function test_unggahan_baru_tersimpan_di_disk_privat(): void
{
    Storage::fake('local');
    Storage::fake('public');

    $dosen = User::factory()->create(['role' => 'dosen']);
    $category = array_key_first(PortfolioCategories::all());

    $this->actingAs($dosen)
        ->post(route('portfolio.store'), [
            'category' => $category,
            'file' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
        ])
        ->assertRedirect();

    $portfolio = $dosen->portfolios()->firstOrFail();

    $this->assertStringStartsWith('portfolios/', $portfolio->file_path);
    Storage::disk('local')->assertExists($portfolio->file_path);
    Storage::disk('public')->assertMissing($portfolio->file_path);
}
```

**Prasyarat:** `User` belum punya relasi `portfolios()`. Tambahkan di `app/Models/User.php`:

```php
public function portfolios()
{
    return $this->hasMany(Portfolio::class);
}
```

(import `App\Models\Portfolio` tidak perlu karena satu namespace.)

**File diubah:** `app/Http/Controllers/PortfolioController.php` — ganti disk `public` menjadi
`local`:

```php
$filePath = $request->file('file')->store('portfolios', 'local');
```

**Verifikasi:**

```powershell
php artisan test --filter=PortfolioUploadTest
```

**Commit:** `fix(portofolio): simpan bukti unggahan di disk privat`

---

## Task 4 — Unduhan lewat route terotorisasi

**Test baru:** `tests/Feature/PortfolioDownloadTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortfolioDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_pemilik_bisa_mengunduh_berkas_privat(): void
    {
        $portfolio = $this->portfolioOn('local');

        $this->actingAs($portfolio->user)
            ->get(route('portfolio.file', $portfolio))
            ->assertOk();
    }

    public function test_jurusan_dan_admin_bisa_mengunduh_berkas(): void
    {
        $portfolio = $this->portfolioOn('local');

        foreach (['jurusan', 'admin'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('portfolio.file', $portfolio))
                ->assertOk();
        }
    }

    public function test_dosen_lain_tidak_boleh_mengunduh(): void
    {
        $portfolio = $this->portfolioOn('local');

        $this->actingAs(User::factory()->create(['role' => 'dosen']))
            ->get(route('portfolio.file', $portfolio))
            ->assertForbidden();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $portfolio = $this->portfolioOn('local');

        $this->get(route('portfolio.file', $portfolio))
            ->assertRedirect(route('login'));
    }

    public function test_berkas_lama_di_disk_publik_tetap_bisa_diunduh(): void
    {
        $portfolio = $this->portfolioOn('public');

        $this->actingAs($portfolio->user)
            ->get(route('portfolio.file', $portfolio))
            ->assertOk();
    }

    public function test_berkas_yang_tidak_ada_di_kedua_disk_menghasilkan_404(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $dosen = User::factory()->create(['role' => 'dosen']);
        $portfolio = Portfolio::create([
            'user_id' => $dosen->id,
            'category' => 'Kinerja Akademik',
            'file_path' => 'portfolios/hilang.pdf',
            'status' => 'uploaded',
            'score' => 0,
        ]);

        $this->actingAs($dosen)
            ->get(route('portfolio.file', $portfolio))
            ->assertNotFound();
    }

    private function portfolioOn(string $disk): Portfolio
    {
        Storage::fake('local');
        Storage::fake('public');

        $dosen = User::factory()->create(['role' => 'dosen']);
        $path = 'portfolios/bukti-'.$disk.'.pdf';

        Storage::disk($disk)->put($path, '%PDF-1.4 contoh');

        return Portfolio::create([
            'user_id' => $dosen->id,
            'category' => 'Kinerja Akademik',
            'file_path' => $path,
            'status' => 'uploaded',
            'score' => 0,
        ]);
    }
}
```

**File baru:** `app/Policies/PortfolioPolicy.php`

```php
<?php

namespace App\Policies;

use App\Models\Portfolio;
use App\Models\User;

class PortfolioPolicy
{
    public function download(User $user, Portfolio $portfolio): bool
    {
        return $portfolio->user_id === $user->id
            || in_array($user->role, ['jurusan', 'admin'], true);
    }
}
```

**File diubah:** `app/Http/Controllers/Controller.php` — basis controller belum memakai trait
otorisasi (Laravel 11 mengosongkannya), jadi `$this->authorize()` akan gagal. Tambahkan:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use AuthorizesRequests;
}
```

**File diubah:** `app/Http/Controllers/PortfolioController.php` — tambah method:

```php
public function download(Portfolio $portfolio)
{
    $this->authorize('download', $portfolio);

    $disk = collect(['local', 'public'])
        ->first(fn (string $disk) => Storage::disk($disk)->exists($portfolio->file_path));

    abort_if($disk === null, 404);

    return Storage::disk($disk)->response($portfolio->file_path);
}
```

**File diubah:** `routes/web.php` — di dalam group `Route::middleware('auth')`, setelah route
`portfolio.store`:

```php
Route::get('/portofolio/{portfolio}/berkas', [PortfolioController::class, 'download'])
    ->name('portfolio.file');
```

**File diubah:** dua view yang masih memakai URL publik.

- `resources/views/dashboard_7stars.blade.php` baris ~216:
  `href="{{ Storage::url($data->file_path) }}"` → `href="{{ route('portfolio.file', $data) }}"`
- `resources/views/jurusan/verifikasi/detail.blade.php` baris ~104:
  `href="{{ Storage::url($p->file_path) }}"` → `href="{{ route('portfolio.file', $p) }}"`

Keduanya tetap `target="_blank"`.

**Verifikasi:**

```powershell
php artisan test --filter=PortfolioDownloadTest
php artisan test
```

**Commit:** `feat(portofolio): unduh bukti lewat route berotorisasi`

---

## Task 5 — Hapus sumber kategori yang duplikat

**File diubah:**

- `app/Http/Controllers/PortfolioController.php`: hapus properti `private $categories` dan method
  statis `getCategories()`.
- `app/Http/Controllers/UserDashboardController.php` baris ~55 dan ~211:
  `PortfolioController::getCategories()` → `PortfolioCategories::all()`, tambah
  `use App\Support\PortfolioCategories;`.
- `app/Http/Controllers/JurusanController.php` baris ~71: sama.
- Tidak ada `use App\Http\Controllers\PortfolioController;` yang perlu dihapus: kedua controller
  berada di namespace yang sama. Cek sisa referensi dengan `grep -rn "PortfolioController" app/`;
  yang tersisa hanya `PortfolioController` sendiri dan `routes/web.php`.

**Verifikasi:**

```powershell
grep -rn "getCategories\|\$categories =" app/ resources/views
php artisan test
php vendor/bin/pint --test app tests
```

Harus tidak ada hasil grep, semua test hijau, Pint PASS.

**Commit:** `refactor(portofolio): hapus daftar kategori duplikat`

---

## Task 6 — Verifikasi akhir dan merge

```powershell
php artisan route:list --name=portfolio
php artisan test
php vendor/bin/pint --test app tests
```

Manual (laporkan apa adanya bila tidak dijalankan):

1. Login sebagai dosen, unggah PDF di salah satu kategori, buka "Lihat File" — harus tampil dari
   `/portofolio/{id}/berkas`, bukan `/storage/...`.
2. Cek `storage/app/private/portfolios` berisi berkas, `storage/app/public/portfolios` tidak
   bertambah.
3. Login sebagai jurusan, buka detail verifikasi, klik "Lihat Dokumen" — harus terbuka.
4. Login sebagai dosen lain, akses URL berkas dosen pertama — harus 403.

Merge setelah semua hijau:

```powershell
git switch main
git merge --no-ff refactor/portfolio-uploads
```

Push hanya setelah dikonfirmasi pemilik repo.

## Definition of done

- `PortfolioController` hanya berisi `store` dan `download` (± 40 baris), tanpa daftar kategori.
- Satu-satunya daftar kategori ada di `App\Support\PortfolioCategories`.
- Tidak ada `Storage::url()` untuk portofolio di view mana pun.
- Test baru: 1 unit + 9 feature, semuanya hijau bersama 49 test lama.
- Pint PASS untuk `app` dan `tests`.
