# Rencana Irisan 5 — Undangan Penilai

> **Untuk pelaksana:** gunakan skill `superpowers:executing-plans` untuk mengeksekusi rencana ini
> task demi task (eksekusi inline di sesi ini, sesuai pilihan pemilik repo).

**Tujuan:** memindahkan logika undangan penilai (batas kuota, cek email ganda, pembuatan token,
pengiriman email, pembatalan undangan) keluar dari `UserDashboardController` ke satu service +
controller domain, tanpa mengubah perilaku yang dilihat pengguna.

**Arsitektur:** `InvitationService` memegang aturan bisnis undangan dan tidak menyentuh HTTP;
`InvitationController` tipis (validasi lewat `StoreInvitationRequest`, policy untuk pembatalan);
`UserDashboardController` tinggal `index`. Batas kuota dan panjang token pindah ke
`config/assessment.php`.

**Stack:** Laravel 12.39, Blade, PHPUnit feature test, SQLite in-memory.

**Spec:** `specs/2026-10-30-refactor-arsitektur-design.md` (bagian "Irisan 5"),
`specs/2026-10-30-refactor-irisan-4-hasil-jurusan.md` (pola yang sama).

## Batasan Global

- Tidak menambah dependency; tidak memakai queue (target shared hosting).
- Nama route `dashboard.invite` dan `invite.destroy` serta URL-nya tidak berubah.
- Pesan sukses/error yang sudah ada dipertahankan bunyinya.
- Email tetap dikirim langsung (`Mail::send`), bukan queue; kegagalan email hanya dicatat.
- `tests/Feature/ScoringCharacterizationTest.php` tidak disentuh.
- `php artisan test` hijau dan `php vendor/bin/pint --test app tests` PASS setiap task.

## Perilaku yang harus tetap sama

| Kasus | Perilaku sekarang |
| --- | --- |
| Atasan ke-3 | `back()` + error `Batas Tercapai: Anda hanya boleh mengundang maksimal 2 Atasan.` |
| Sejawat ke-6 | `back()` + error `Batas Tercapai: Anda hanya boleh mengundang maksimal 5 Teman Sejawat.` |
| Email sama, hubungan sama | `back()` + error `Email orang ini sudah Anda undang sebelumnya.` |
| Undangan baru | baris `assessment_requests` dengan token acak 32 karakter, `is_completed = false` |
| Email terkirim | `back()` + success `Undangan berhasil dibuat & email telah dikirim!` |
| Email gagal | undangan tetap tersimpan, log error, success `Undangan dibuat, tapi Email gagal terkirim. Silakan bagikan Link Manual.` |
| Batalkan milik sendiri, belum selesai | baris dihapus, success `Undangan berhasil dibatalkan.` |
| Batalkan milik orang lain | `403` |
| Batalkan yang sudah selesai | `back()` + error `Tidak bisa membatalkan penilaian yang sudah selesai.` |
| Tanpa periode aktif | `404` (perilaku lama `firstOrFail()`) |

Perubahan yang disengaja (dicatat): `name`/`email` mendapat batas `max:255` supaya nama lebih
panjang tidak berakhir 500 dari kolom `varchar(255)`; pesan validasi memakai Bahasa Indonesia;
owner diperiksa **sebelum** status selesai supaya non-owner tidak bisa menebak status undangan
orang lain (tetap 403).

Di luar cakupan: `relationship=self` tidak dibatasi jumlahnya (perilaku lama); email ganda dicek
per hubungan, jadi orang yang sama masih bisa diundang sebagai atasan **dan** sejawat (perilaku
lama); undangan tidak kedaluwarsa.

---

### Task 0: Siapkan branch

- [ ] **Step 1:**

```powershell
git switch main
git switch -c refactor/invitation
```

Kriteria: `git status --short` hanya `AGENTS.md`, `.opencode/`, `logs/`.

---

### Task 1: Config, FormRequest, exception, service

**Files:**
- Create: `config/assessment.php`
- Create: `app/Http/Requests/StoreInvitationRequest.php`
- Create: `app/Exceptions/InvitationException.php`
- Create: `app/Services/InvitationService.php`
- Test: `tests/Feature/InvitationTest.php`

**Interfaces:**
- Consumes: `AssessmentRequest` (fillable bebas, cast `is_completed` boolean),
  `AssessmentInvitation` mailable, `AssessmentPeriod`, `User`.
- Produces:
  - `InvitationService::invite(User $user, AssessmentPeriod $period, array $data): array{request: AssessmentRequest, email_sent: bool}`,
    melempar `InvitationException` (pesan Bahasa Indonesia) saat kuota penuh atau email ganda.
  - `config('assessment.invitation.max_superior' | 'max_peer' | 'token_length')`.
  - `StoreInvitationRequest::rules()` untuk `name`, `email`, `relationship`.

- [ ] **Step 1: Tulis test yang gagal**

```php
<?php

namespace Tests\Feature;

use App\Mail\AssessmentInvitation;
use App\Models\AssessmentPeriod;
use App\Models\AssessmentRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_undangan_atasan_dibatasi_dua(): void
    {
        Mail::fake();
        $dosen = $this->dosen();
        $this->existingInvitations($dosen, 'superior', 2);

        $this->actingAs($dosen)
            ->from(route('dashboard.index'))
            ->post(route('dashboard.invite'), $this->payload('superior', 'atasan3@example.test'))
            ->assertRedirect(route('dashboard.index'))
            ->assertSessionHas('error', 'Batas Tercapai: Anda hanya boleh mengundang maksimal 2 Atasan.');

        $this->assertSame(2, $this->invitationCount($dosen, 'superior'));
        Mail::assertNothingSent();
    }

    public function test_undangan_sejawat_dibatasi_lima(): void
    {
        Mail::fake();
        $dosen = $this->dosen();
        $this->existingInvitations($dosen, 'peer', 5);

        $this->actingAs($dosen)
            ->from(route('dashboard.index'))
            ->post(route('dashboard.invite'), $this->payload('peer', 'sejawat6@example.test'))
            ->assertSessionHas('error', 'Batas Tercapai: Anda hanya boleh mengundang maksimal 5 Teman Sejawat.');

        $this->assertSame(5, $this->invitationCount($dosen, 'peer'));
        Mail::assertNothingSent();
    }

    public function test_batas_diambil_dari_config(): void
    {
        Mail::fake();
        config(['assessment.invitation.max_superior' => 1]);

        $dosen = $this->dosen();
        $this->existingInvitations($dosen, 'superior', 1);

        $this->actingAs($dosen)
            ->post(route('dashboard.invite'), $this->payload('superior', 'atasan2@example.test'))
            ->assertSessionHas('error', 'Batas Tercapai: Anda hanya boleh mengundang maksimal 1 Atasan.');

        $this->assertSame(1, $this->invitationCount($dosen, 'superior'));
    }

    public function test_email_yang_sama_tidak_bisa_diundang_dua_kali(): void
    {
        Mail::fake();
        $dosen = $this->dosen();
        $this->existingInvitations($dosen, 'peer', 1, 'sama@example.test');

        $this->actingAs($dosen)
            ->post(route('dashboard.invite'), $this->payload('peer', 'sama@example.test'))
            ->assertSessionHas('error', 'Email orang ini sudah Anda undang sebelumnya.');

        $this->assertSame(1, $this->invitationCount($dosen, 'peer'));
        Mail::assertNothingSent();
    }

    public function test_undangan_tersimpan_dan_email_dikirim(): void
    {
        Mail::fake();
        $dosen = $this->dosen();

        $this->actingAs($dosen)
            ->from(route('dashboard.index'))
            ->post(route('dashboard.invite'), $this->payload('peer', 'baru@example.test'))
            ->assertRedirect(route('dashboard.index'))
            ->assertSessionHas('success', 'Undangan berhasil dibuat & email telah dikirim!');

        $invitation = AssessmentRequest::firstOrFail();

        $this->assertSame($dosen->id, $invitation->user_id);
        $this->assertSame('peer', $invitation->relationship);
        $this->assertSame('baru@example.test', $invitation->assessor_email);
        $this->assertFalse($invitation->is_completed);
        $this->assertSame(config('assessment.invitation.token_length'), strlen($invitation->access_token));

        Mail::assertSent(AssessmentInvitation::class, fn ($mail) => $mail->hasTo('baru@example.test'));
    }

    public function test_self_assessment_tidak_dibatasi(): void
    {
        Mail::fake();
        $dosen = $this->dosen();

        $this->actingAs($dosen)
            ->post(route('dashboard.invite'), [
                'name' => $dosen->name,
                'email' => $dosen->email,
                'relationship' => 'self',
            ])
            ->assertSessionHas('success');

        $this->assertSame(1, $this->invitationCount($dosen, 'self'));
    }

    public function test_hubungan_tidak_dikenal_ditolak(): void
    {
        Mail::fake();
        $dosen = $this->dosen();

        $this->actingAs($dosen)
            ->post(route('dashboard.invite'), $this->payload('musuh', 'x@example.test'))
            ->assertSessionHasErrors('relationship');

        $this->assertSame(0, AssessmentRequest::count());
        Mail::assertNothingSent();
    }

    public function test_kegagalan_email_tidak_menggagalkan_undangan(): void
    {
        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('smtp mati'));
        Log::spy();
        $dosen = $this->dosen();

        $this->actingAs($dosen)
            ->from(route('dashboard.index'))
            ->post(route('dashboard.invite'), $this->payload('superior', 'gagal@example.test'))
            ->assertRedirect(route('dashboard.index'))
            ->assertSessionHas('success', 'Undangan dibuat, tapi Email gagal terkirim. Silakan bagikan Link Manual.');

        $this->assertSame(1, AssessmentRequest::count());
        Log::shouldHaveReceived('error')->once();
    }

    private function dosen(): User
    {
        $dosen = User::factory()->create(['role' => 'dosen']);

        AssessmentPeriod::forceCreate(['name' => 'Periode Uji', 'is_active' => true]);

        return $dosen;
    }

    private function payload(string $relationship, string $email): array
    {
        return ['name' => 'Penilai Uji', 'email' => $email, 'relationship' => $relationship];
    }

    private function existingInvitations(User $user, string $relationship, int $count, ?string $email = null): void
    {
        $period = AssessmentPeriod::where('is_active', true)->firstOrFail();

        for ($i = 1; $i <= $count; $i++) {
            AssessmentRequest::forceCreate([
                'user_id' => $user->id,
                'assessment_period_id' => $period->id,
                'assessor_name' => 'Penilai '.$i,
                'assessor_email' => $i === 1 && $email ? $email : $relationship.$i.'@example.test',
                'relationship' => $relationship,
                'access_token' => Str::random(32),
                'is_completed' => false,
            ]);
        }
    }

    private function invitationCount(User $user, string $relationship): int
    {
        return AssessmentRequest::where('user_id', $user->id)
            ->where('relationship', $relationship)
            ->count();
    }
}
```

- [ ] **Step 2: Jalankan test, pastikan gagal**

```powershell
php artisan test --filter=InvitationTest
```

Harapan: gagal karena `config('assessment.invitation.*')` belum ada dan `InvitationService` belum ada
(test yang tidak menyentuh config boleh lolos sementara lewat controller lama).

- [ ] **Step 3: Buat `config/assessment.php`**

```php
<?php

return [
    'invitation' => [
        'max_superior' => (int) env('ASSESSMENT_MAX_SUPERIOR', 2),
        'max_peer' => (int) env('ASSESSMENT_MAX_PEER', 5),
        'token_length' => (int) env('ASSESSMENT_TOKEN_LENGTH', 32),
    ],
];
```

- [ ] **Step 4: Buat `StoreInvitationRequest`**

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvitationRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'relationship' => ['required', 'in:superior,peer,self'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama penilai wajib diisi.',
            'name.max' => 'Nama penilai maksimal 255 karakter.',
            'email.required' => 'Email penilai wajib diisi.',
            'email.email' => 'Format email penilai tidak valid.',
            'email.max' => 'Email penilai maksimal 255 karakter.',
            'relationship.required' => 'Hubungan penilai wajib dipilih.',
            'relationship.in' => 'Hubungan penilai harus Atasan, Sejawat, atau Diri Sendiri.',
        ];
    }
}
```

- [ ] **Step 5: Buat `app/Exceptions/InvitationException.php`**

```php
<?php

namespace App\Exceptions;

use RuntimeException;

class InvitationException extends RuntimeException {}
```

- [ ] **Step 6: Buat `app/Services/InvitationService.php`**

```php
<?php

namespace App\Services;

use App\Exceptions\InvitationException;
use App\Mail\AssessmentInvitation;
use App\Models\AssessmentPeriod;
use App\Models\AssessmentRequest;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Aturan undangan penilai: kuota per hubungan, larangan email ganda,
 * pembuatan token, dan pengiriman email yang kegagalannya hanya dicatat.
 */
class InvitationService
{
    /**
     * @param  array{name: string, email: string, relationship: string}  $data
     * @return array{request: AssessmentRequest, email_sent: bool}
     *
     * @throws InvitationException
     */
    public function invite(User $user, AssessmentPeriod $period, array $data): array
    {
        $relationship = $data['relationship'];

        $this->ensureWithinQuota($user, $period, $relationship);
        $this->ensureNotInvited($user, $period, $data);

        $invitation = AssessmentRequest::create([
            'user_id' => $user->id,
            'assessment_period_id' => $period->id,
            'assessor_name' => $data['name'],
            'assessor_email' => $data['email'],
            'relationship' => $relationship,
            'access_token' => Str::random((int) config('assessment.invitation.token_length')),
        ]);

        return [
            'request' => $invitation,
            'email_sent' => $this->sendInvitation($invitation),
        ];
    }

    private function ensureWithinQuota(User $user, AssessmentPeriod $period, string $relationship): void
    {
        $quota = match ($relationship) {
            'superior' => [
                'max' => (int) config('assessment.invitation.max_superior'),
                'label' => 'Atasan',
            ],
            'peer' => [
                'max' => (int) config('assessment.invitation.max_peer'),
                'label' => 'Teman Sejawat',
            ],
            default => null, // self tidak dibatasi, sama seperti sebelumnya
        };

        if ($quota === null) {
            return;
        }

        $current = AssessmentRequest::where('user_id', $user->id)
            ->where('assessment_period_id', $period->id)
            ->where('relationship', $relationship)
            ->count();

        if ($current >= $quota['max']) {
            throw new InvitationException(
                "Batas Tercapai: Anda hanya boleh mengundang maksimal {$quota['max']} {$quota['label']}."
            );
        }
    }

    /**
     * @param  array{name: string, email: string, relationship: string}  $data
     */
    private function ensureNotInvited(User $user, AssessmentPeriod $period, array $data): void
    {
        $exists = AssessmentRequest::where('user_id', $user->id)
            ->where('assessment_period_id', $period->id)
            ->where('relationship', $data['relationship'])
            ->where('assessor_email', $data['email'])
            ->exists();

        if ($exists) {
            throw new InvitationException('Email orang ini sudah Anda undang sebelumnya.');
        }
    }

    private function sendInvitation(AssessmentRequest $invitation): bool
    {
        try {
            Mail::to($invitation->assessor_email)->send(new AssessmentInvitation($invitation));

            return true;
        } catch (\Throwable $e) {
            // Undangan tetap sah walau SMTP bermasalah; link bisa dibagikan manual.
            Log::error('Gagal kirim email undangan: '.$e->getMessage());

            return false;
        }
    }
}
```

- [ ] **Step 7: Jalankan test, pastikan lulus**

```powershell
php artisan test --filter=InvitationTest
php vendor/bin/pint app/Http/Requests/StoreInvitationRequest.php app/Policies app/Services/InvitationService.php app/Exceptions config/assessment.php tests/Feature/InvitationTest.php
```

- [ ] **Step 8: Commit**

```powershell
git add config/assessment.php app/Http/Requests/StoreInvitationRequest.php app/Exceptions/InvitationException.php app/Services/InvitationService.php tests/Feature/InvitationTest.php
git commit -m "refactor(undangan): satukan aturan undangan penilai dalam service"
```

---

### Task 2: `InvitationController`, policy, route, pemangkasan dashboard

**Files:**
- Create: `app/Http/Controllers/InvitationController.php`
- Create: `app/Policies/AssessmentRequestPolicy.php`
- Modify: `app/Http/Controllers/UserDashboardController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/InvitationTest.php`

- [ ] **Step 1: Tambah test pembatalan dan route**

Tambahkan ke `InvitationTest`:

```php
public function test_penilai_bisa_membatalkan_undangannya_sendiri(): void
{
    $dosen = $this->dosen();
    $invitation = $this->oneInvitation($dosen, 'peer');

    $this->actingAs($dosen)
        ->from(route('dashboard.index'))
        ->delete(route('invite.destroy', $invitation->id))
        ->assertRedirect(route('dashboard.index'))
        ->assertSessionHas('success', 'Undangan berhasil dibatalkan.');

    $this->assertDatabaseMissing('assessment_requests', ['id' => $invitation->id]);
}

public function test_tidak_bisa_membatalkan_undangan_orang_lain(): void
{
    $pemilik = $this->dosen();
    $lain = $this->dosen();
    $invitation = $this->oneInvitation($pemilik, 'peer');

    $this->actingAs($lain)
        ->delete(route('invite.destroy', $invitation->id))
        ->assertForbidden();

    $this->assertDatabaseHas('assessment_requests', ['id' => $invitation->id]);
}

public function test_tidak_bisa_membatalkan_undangan_yang_sudah_selesai(): void
{
    $dosen = $this->dosen();
    $invitation = $this->oneInvitation($dosen, 'peer');
    $invitation->forceFill(['is_completed' => true, 'completed_at' => now()])->save();

    $this->actingAs($dosen)
        ->from(route('dashboard.index'))
        ->delete(route('invite.destroy', $invitation->id))
        ->assertRedirect(route('dashboard.index'))
        ->assertSessionHas('error', 'Tidak bisa membatalkan penilaian yang sudah selesai.');

    $this->assertDatabaseHas('assessment_requests', ['id' => $invitation->id]);
}

public function test_non_owner_tetap_403_untuk_undangan_yang_selesai(): void
{
    $pemilik = $this->dosen();
    $lain = $this->dosen();
    $invitation = $this->oneInvitation($pemilik, 'peer');
    $invitation->forceFill(['is_completed' => true, 'completed_at' => now()])->save();

    $this->actingAs($lain)
        ->delete(route('invite.destroy', $invitation->id))
        ->assertForbidden();
}

private function oneInvitation(User $user, string $relationship): AssessmentRequest
{
    $this->existingInvitations($user, $relationship, 1);

    return AssessmentRequest::where('user_id', $user->id)->firstOrFail();
}
```

- [ ] **Step 2: Jalankan, pastikan test pembatalan gagal/menembak controller lama**

```powershell
php artisan test --filter=InvitationTest
```

- [ ] **Step 3: Buat `AssessmentRequestPolicy`**

```php
<?php

namespace App\Policies;

use App\Models\AssessmentRequest;
use App\Models\User;

class AssessmentRequestPolicy
{
    public function delete(User $user, AssessmentRequest $assessmentRequest): bool
    {
        return $assessmentRequest->user_id === $user->id;
    }
}
```

- [ ] **Step 4: Buat `InvitationController`**

```php
<?php

namespace App\Http\Controllers;

use App\Exceptions\InvitationException;
use App\Http\Requests\StoreInvitationRequest;
use App\Models\AssessmentPeriod;
use App\Models\AssessmentRequest;
use App\Services\InvitationService;

class InvitationController extends Controller
{
    public function __construct(private InvitationService $invitations) {}

    public function store(StoreInvitationRequest $request)
    {
        $period = AssessmentPeriod::where('is_active', true)->firstOrFail();

        try {
            $result = $this->invitations->invite($request->user(), $period, $request->validated());
        } catch (InvitationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with(
            'success',
            $result['email_sent']
                ? 'Undangan berhasil dibuat & email telah dikirim!'
                : 'Undangan dibuat, tapi Email gagal terkirim. Silakan bagikan Link Manual.'
        );
    }

    public function destroy(AssessmentRequest $assessmentRequest)
    {
        $this->authorize('delete', $assessmentRequest);

        if ($assessmentRequest->is_completed) {
            return back()->with('error', 'Tidak bisa membatalkan penilaian yang sudah selesai.');
        }

        $assessmentRequest->delete();

        return back()->with('success', 'Undangan berhasil dibatalkan.');
    }
}
```

- [ ] **Step 5: Pindahkan route**

`routes/web.php`:
- `Route::post('/undang-penilai', [InvitationController::class, 'store'])->name('dashboard.invite');`
- `Route::delete('/cancel-invite/{assessmentRequest}', [InvitationController::class, 'destroy'])->name('invite.destroy');`
- Tambahkan `use App\Http\Controllers\InvitationController;`.

- [ ] **Step 6: Pangkas `UserDashboardController`**

Hapus `invite`, `destroyRequest`, dan import yang tidak terpakai (`AssessmentInvitation`, `Request`,
`Log`, `Mail`, `Str`). `index` tidak berubah. Target: < 110 baris.

- [ ] **Step 7: Verifikasi**

```powershell
php artisan test
php vendor/bin/pint --test app tests
wc -l app/Http/Controllers/UserDashboardController.php app/Http/Controllers/InvitationController.php
php artisan route:list --name=invite
```

Harapan: seluruh suite hijau (95 + 11 test baru), Pint PASS, `UserDashboardController` < 110 baris,
`InvitationController` < 60 baris, route `dashboard.invite` dan `invite.destroy` ada.

- [ ] **Step 8: Commit**

```powershell
git add app/Http/Controllers/InvitationController.php app/Policies/AssessmentRequestPolicy.php app/Http/Controllers/UserDashboardController.php routes/web.php tests/Feature/InvitationTest.php
git commit -m "refactor(undangan): pisahkan controller undangan dan tambah policy"
```

---

### Task 3: Verifikasi nyata dan merge

- [ ] **Step 1: Cek statis**

```powershell
php artisan test
php vendor/bin/pint --test app tests
php artisan route:list --name=invite
```

- [ ] **Step 2: Browser (laporkan apa adanya bila tidak dijalankan)**

1. `php artisan serve --port=8086`, login sebagai dosen.
2. Buat undangan atasan pertama → sukses; buat atasan kedua → sukses; atasan ketiga → pesan batas.
3. Batalkan undangan yang belum selesai → hilang dari daftar.
4. Coba `DELETE /cancel-invite/{id milik dosen lain}` lewat fetch → 403.

- [ ] **Step 3: Merge**

```powershell
git switch main
git merge --no-ff refactor/invitation
```

Push hanya setelah dikonfirmasi pemilik repo.

## Definition of Done

- `UserDashboardController` hanya berisi `index`.
- `InvitationService` memegang kuota, cek email ganda, token, dan email; controller tidak menyentuh
  aturan itu.
- Batas kuota dan panjang token dibaca dari `config/assessment.php`.
- Pembatalan undangan memakai policy; non-owner 403; undangan selesai tidak bisa dihapus.
- Test baru mencakup kuota (termasuk override config), email ganda, self assessment, email gagal,
  validasi hubungan, pembatalan (sendiri/orang lain/selesai), dan jalur sukses.
- Suite penuh hijau, Pint PASS.
