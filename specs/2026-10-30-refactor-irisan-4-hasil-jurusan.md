# Rencana Irisan 4 — Hasil Jurusan

> **Untuk pelaksana:** gunakan skill `superpowers:executing-plans` untuk mengeksekusi rencana ini
> task demi task (eksekusi inline di sesi ini).

**Tujuan:** membuat halaman hasil dan PDF jurusan memakai `AssessmentResultService` yang sama
dengan dosen, sehingga rincian dimensi tidak mungkin berbeda antar peran.

**Arsitektur:** `JurusanController` menyelesaikan periode, memanggil
`AssessmentResultService::viewDataFor($dosen, $period)`, lalu mengembalikan view/PDF. Perhitungan
skor dan pemilihan analisis tidak lagi disalin di controller.

**Stack:** Laravel 12.39, Blade, DomPDF, PHPUnit feature test.

**Spec:** `specs/2026-10-30-refactor-arsitektur-design.md` (bagian "Irisan 4"),
`specs/2026-10-30-refactor-irisan-3-hasil-dosen.md` (service yang dipakai).

## Batasan Global

- Tidak menambah dependency; tidak mengubah `AssessmentScoreService`, `AiNarrativeService`, atau
  `AssessmentResultService`.
- Nama route `jurusan.result`, `jurusan.result.pdf` dan URL tidak berubah.
- Pesan error yang sudah ada dipertahankan bunyinya.
- `tests/Feature/ScoringCharacterizationTest.php` tidak disentuh.
- `php artisan test` hijau dan `php vendor/bin/pint --test app tests` PASS.
- Middleware `auth` + `role:jurusan` pada route tidak berubah.

## Fokus Review

- Dosen dan jurusan membuka hasil untuk data yang sama → rincian dimensi identik.
- PDF jurusan memakai rincian dimensi yang sama dengan halaman jurusan, bukan perhitungan sendiri.
- Dosen tanpa analisis → jurusan dapat pesan error, bukan 500.
- Tidak ada periode aktif → redirect dengan pesan, bukan 500.
- PDF tetap bisa diunduh dan nama berkasnya tetap `Laporan_<nama>.pdf`.

---

### Task 0: Siapkan branch

- [ ] **Step 1:**

```powershell
git switch main
git switch -c refactor/result-jurusan
```

Kriteria: `git status --short` hanya `AGENTS.md`, `.opencode/`, `logs/`.

---

### Task 1: Jurusan memakai `AssessmentResultService`

**Files:**
- Modify: `app/Http/Controllers/JurusanController.php`
- Test: `tests/Feature/JurusanVerificationScoringTest.php`

**Interfaces:**
- Consumes: `AssessmentResultService::viewDataFor(User $user, AssessmentPeriod $period): ?array{analysis: AiAnalysis, dimensionDetails: array<string, float>}`.
- Produces: route `jurusan.result` dan `jurusan.result.pdf` berperilaku sama, dengan sumber data
  tunggal.

- [ ] **Step 1: Tambah test yang gagal di `JurusanVerificationScoringTest.php`**

```php
public function test_rincian_dimensi_jurusan_identik_dengan_halaman_hasil_dosen(): void
{
    [$dosen, $jurusan] = $this->dosenDanJurusan();

    $dosenDetails = $this->actingAs($dosen)
        ->get(route('result.show'))
        ->viewData('dimensionDetails');

    $jurusanDetails = $this->actingAs($jurusan)
        ->get(route('jurusan.result', $dosen->id))
        ->assertOk()
        ->viewData('dimensionDetails');

    $this->assertSame($dosenDetails, $jurusanDetails);
}

public function test_pdf_jurusan_terunduh_dengan_nama_berkas_yang_sama(): void
{
    [$dosen, $jurusan] = $this->dosenDanJurusan();

    $response = $this->actingAs($jurusan)->get(route('jurusan.result.pdf', $dosen->id));

    $response->assertOk();
    $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
    $this->assertStringContainsString('Laporan_'.$dosen->name.'.pdf', (string) $response->headers->get('content-disposition'));
}

public function test_hasil_jurusan_mengalihkan_bila_dosen_belum_punya_analisis(): void
{
    $dosen = User::factory()->create(['role' => 'dosen']);
    $jurusan = User::factory()->create(['role' => 'jurusan']);
    AssessmentPeriod::forceCreate(['name' => 'Periode Uji', 'is_active' => true]);

    $this->actingAs($jurusan)
        ->from(route('jurusan.verifikasi.index'))
        ->get(route('jurusan.result', $dosen->id))
        ->assertRedirect(route('jurusan.verifikasi.index'))
        ->assertSessionHas('error', 'Dosen ini belum melakukan proses analisis hasil.');
}

private function dosenDanJurusan(): array
{
    $dosen = User::factory()->create(['role' => 'dosen']);
    $jurusan = User::factory()->create(['role' => 'jurusan']);
    $period = AssessmentPeriod::forceCreate(['name' => 'Periode Uji', 'is_active' => true]);

    $this->responses($dosen, $period, 'superior', 'Profesionalisme', [5, 5]);
    $this->responses($dosen, $period, 'peer', 'Profesionalisme', [3, 3]);
    $this->responses($dosen, $period, 'superior', 'Kolaborasi', [2, 4]);
    $this->storedAnalysis($dosen, $period);

    return [$dosen, $jurusan];
}
```

- [ ] **Step 2: Jalankan, pastikan red/bermakna**

```powershell
php artisan test --filter=JurusanVerificationScoringTest
```

Harapan: test identik dosen-vs-jurusan dan PDF boleh sudah hijau karena keduanya memakai
perhitungan yang sama; test "belum punya analisis" jadi jaring pengaman saat controller dipindah.

- [ ] **Step 3: Ubah `JurusanController`**

- Constructor menjadi `public function __construct(private AssessmentResultService $results) {}`;
  hapus `AssessmentScoreService`.
- `viewResult($userId)`:

```php
$dosen = User::findOrFail($userId);
$period = AssessmentPeriod::where('is_active', true)->first();

if (! $period) {
    return back()->with('error', 'Tidak ada periode penilaian aktif.');
}

$data = $this->results->viewDataFor($dosen, $period);

if ($data === null) {
    return back()->with('error', 'Dosen ini belum melakukan proses analisis hasil.');
}

return view('jurusan.verifikasi.result', [
    'dosen' => $dosen,
    'analysis' => $data['analysis'],
    'dimensionDetails' => $data['dimensionDetails'],
]);
```

- `exportPdf($userId)`: periode dulu (`'Tidak ada periode penilaian aktif.'`), lalu
  `viewDataFor()` null → `back()->with('error', 'Dosen ini belum memproses hasil analisis.')`,
  lalu `Pdf::loadView('pdf.report', ['user' => $dosen, 'analysis' => ..., 'setting' => UniversitySetting::first(), 'dimensionDetails' => ...])`
  dan `->download('Laporan_'.$dosen->name.'.pdf')`.
- Hapus import yang tidak terpakai (`AiAnalysis`, `AssessmentScoreService`). `dashboard`,
  `verificationIndex`, `show`, `storeScore` tidak berubah.

- [ ] **Step 4: Verifikasi**

```powershell
php artisan test
php vendor/bin/pint --test app tests
wc -l app/Http/Controllers/JurusanController.php
```

Harapan: seluruh suite hijau (90 + 5 test baru), Pint PASS. Controller tetap ~150 baris karena
`dashboard`, `verificationIndex`, `show`, dan `storeScore` sengaja tidak diubah di irisan ini;
pemecahan controller jurusan dijadwalkan terpisah.

- [ ] **Step 5: Commit**

```powershell
git add app/Http/Controllers/JurusanController.php tests/Feature/JurusanVerificationScoringTest.php
git commit -m "refactor(hasil): jurusan memakai service hasil yang sama dengan dosen"
```

---

### Task 2: Verifikasi nyata dan merge

- [ ] **Step 1: Cek statis**

```powershell
php artisan test
php vendor/bin/pint --test app tests
php artisan route:list --name=jurusan.result
```

- [ ] **Step 2: Browser (laporkan apa adanya bila tidak dijalankan)**

1. `php artisan serve --port=8085`, login sebagai user `jurusan`.
2. Buka daftar verifikasi → pilih dosen yang punya hasil → halaman hasil menampilkan bintang,
   skor, dan rincian dimensi.
3. Unduh PDF → berkas `Laporan_<nama>.pdf` terunduh dan terbuka.
4. Bandingkan angka rincian dimensi dengan halaman hasil dosen untuk dosen yang sama.

- [ ] **Step 3: Merge**

```powershell
git switch main
git merge --no-ff refactor/result-jurusan
```

Push hanya setelah dikonfirmasi pemilik repo.

## Definition of Done

- `JurusanController` tidak lagi memakai `AssessmentScoreService` atau query `AiAnalysis` langsung
  untuk hasil.
- Halaman hasil jurusan dan dosen memakai `AssessmentResultService::viewDataFor()` yang sama.
- Test paritas dosen-vs-jurusan ada dan hijau; PDF jurusan teruji 200 + nama berkas.
- Suite penuh hijau, Pint PASS.
