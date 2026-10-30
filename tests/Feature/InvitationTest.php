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
        config(['assessment.invitation.token_length' => 20]);
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
        $this->assertSame(20, strlen($invitation->access_token), 'Panjang token harus mengikuti config.');

        Mail::assertSent(AssessmentInvitation::class, fn ($mail) => $mail->hasTo('baru@example.test'));
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
        // Kegagalan SMTP nyata terjadi saat send(), bukan saat to().
        Mail::shouldReceive('to->send')->once()->andThrow(new \RuntimeException('smtp mati'));
        Log::spy();
        $dosen = $this->dosen();

        $this->actingAs($dosen)
            ->from(route('dashboard.index'))
            ->post(route('dashboard.invite'), $this->payload('superior', 'gagal@example.test'))
            ->assertRedirect(route('dashboard.index'))
            ->assertSessionHas('success', 'Undangan dibuat, tapi Email gagal terkirim. Silakan bagikan Link Manual.');

        $this->assertSame(1, AssessmentRequest::count());
        Log::shouldHaveReceived('error')
            ->withArgs(fn (string $message) => str_contains($message, 'Gagal kirim email undangan: smtp mati'))
            ->once();
    }

    public function test_self_assessment_tidak_punya_kuota(): void
    {
        Mail::fake();
        $dosen = $this->dosen();

        // Email tetap tidak boleh ganda; yang diuji di sini hanya tidak adanya kuota.
        for ($i = 1; $i <= 3; $i++) {
            $this->actingAs($dosen)
                ->post(route('dashboard.invite'), [
                    'name' => $dosen->name,
                    'email' => 'self'.$i.'@example.test',
                    'relationship' => 'self',
                ])
                ->assertSessionHas('success');
        }

        $this->assertSame(3, $this->invitationCount($dosen, 'self'));
    }

    public function test_kuota_hanya_menghitung_periode_aktif(): void
    {
        Mail::fake();
        $dosen = $this->dosen();
        $lama = AssessmentPeriod::forceCreate(['name' => 'Periode Lalu', 'is_active' => false]);

        for ($i = 1; $i <= 2; $i++) {
            AssessmentRequest::forceCreate([
                'user_id' => $dosen->id,
                'assessment_period_id' => $lama->id,
                'assessor_name' => 'Atasan Lama '.$i,
                'assessor_email' => 'lama'.$i.'@example.test',
                'relationship' => 'superior',
                'access_token' => Str::random(32),
                'is_completed' => false,
            ]);
        }

        $this->actingAs($dosen)
            ->post(route('dashboard.invite'), $this->payload('superior', 'baru@example.test'))
            ->assertSessionHas('success');

        $activePeriod = AssessmentPeriod::where('is_active', true)->firstOrFail();

        $this->assertSame(1, AssessmentRequest::where('user_id', $dosen->id)
            ->where('assessment_period_id', $activePeriod->id)
            ->where('relationship', 'superior')
            ->count());
    }

    public function test_email_sama_boleh_diundang_dengan_hubungan_berbeda(): void
    {
        Mail::fake();
        $dosen = $this->dosen();
        $this->existingInvitations($dosen, 'superior', 1, 'sama@example.test');

        $this->actingAs($dosen)
            ->post(route('dashboard.invite'), $this->payload('peer', 'sama@example.test'))
            ->assertSessionHas('success');

        $this->assertSame(1, $this->invitationCount($dosen, 'peer'));
    }

    public function test_nama_dan_email_terlalu_panjang_ditolak(): void
    {
        Mail::fake();
        $dosen = $this->dosen();

        $this->actingAs($dosen)
            ->post(route('dashboard.invite'), [
                'name' => str_repeat('a', 256),
                'email' => str_repeat('a', 250).'@example.test',
                'relationship' => 'peer',
            ])
            ->assertSessionHasErrors(['name', 'email']);

        $this->assertSame(0, AssessmentRequest::count());
    }

    public function test_konfigurasi_hilang_tetap_memakai_nilai_aman(): void
    {
        Mail::fake();
        $dosen = $this->dosen();
        config(['assessment.invitation' => []]);

        // Kuota kembali ke default 2 (bukan 0, yang memblokir semua undangan).
        $this->actingAs($dosen)
            ->post(route('dashboard.invite'), $this->payload('superior', 'aman@example.test'))
            ->assertSessionHas('success');

        $invitation = AssessmentRequest::firstOrFail();
        $this->assertSame(32, strlen($invitation->access_token), 'Token tidak boleh kosong saat config hilang.');

        $this->existingInvitations($dosen, 'superior', 1);
        $this->actingAs($dosen)
            ->post(route('dashboard.invite'), $this->payload('superior', 'ketiga@example.test'))
            ->assertSessionHas('error', 'Batas Tercapai: Anda hanya boleh mengundang maksimal 2 Atasan.');
    }

    public function test_token_length_nol_tidak_menghasilkan_token_kosong(): void
    {
        Mail::fake();
        $dosen = $this->dosen();
        config(['assessment.invitation.token_length' => 0]);

        $this->actingAs($dosen)
            ->post(route('dashboard.invite'), $this->payload('superior', 'nol1@example.test'))
            ->assertSessionHas('success');
        $this->actingAs($dosen)
            ->post(route('dashboard.invite'), $this->payload('peer', 'nol2@example.test'))
            ->assertSessionHas('success');

        $tokens = AssessmentRequest::pluck('access_token');

        $this->assertCount(2, $tokens, 'Undangan kedua tidak boleh gagal karena token bentrok.');
        $this->assertSame([16, 16], $tokens->map(fn ($token) => strlen($token))->all());
        $this->assertNotSame($tokens[0], $tokens[1], 'Token harus acak, bukan konstanta.');
    }

    public function test_token_lebih_panjang_dari_kolom_dipotong_ke_64(): void
    {
        Mail::fake();
        $dosen = $this->dosen();
        config(['assessment.invitation.token_length' => 100]);

        $this->actingAs($dosen)
            ->post(route('dashboard.invite'), $this->payload('peer', 'panjang@example.test'))
            ->assertSessionHas('success');

        $this->assertSame(64, strlen(AssessmentRequest::firstOrFail()->access_token));
    }

    public function test_kuota_nol_di_config_tidak_memblokir_semua_undangan(): void
    {
        Mail::fake();
        $dosen = $this->dosen();
        config(['assessment.invitation.max_superior' => 0]);

        $this->actingAs($dosen)
            ->post(route('dashboard.invite'), $this->payload('superior', 'satu@example.test'))
            ->assertSessionHas('success');

        $this->actingAs($dosen)
            ->post(route('dashboard.invite'), $this->payload('superior', 'dua@example.test'))
            ->assertSessionHas('error', 'Batas Tercapai: Anda hanya boleh mengundang maksimal 1 Atasan.');
    }

    public function test_dashboard_menampilkan_batas_dari_config(): void
    {
        $dosen = $this->dosen();
        config(['assessment.invitation.max_superior' => 0, 'assessment.invitation.max_peer' => 9]);

        $this->actingAs($dosen)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('Atasan (0/1)')
            ->assertSee('Sejawat (0/9)');
    }

    public function test_undangan_di_luar_kuota_tidak_menyisakan_baris(): void
    {
        Mail::fake();
        $dosen = $this->dosen();
        $this->existingInvitations($dosen, 'superior', 2);

        $this->actingAs($dosen)
            ->from(route('dashboard.index'))
            ->post(route('dashboard.invite'), $this->payload('superior', 'atasan3@example.test'))
            ->assertSessionHas('error');

        $this->assertSame(2, $this->invitationCount($dosen, 'superior'));
        Mail::assertNothingSent();
    }

    public function test_undangan_tanpa_periode_aktif_ditolak_dengan_pesan(): void
    {
        Mail::fake();
        $dosen = User::factory()->create(['role' => 'dosen']);

        $this->actingAs($dosen)
            ->from(route('dashboard.index'))
            ->post(route('dashboard.invite'), $this->payload('peer', 'x@example.test'))
            ->assertRedirect(route('dashboard.index'))
            ->assertSessionHas('error', 'Tidak ada periode aktif.');

        $this->assertSame(0, AssessmentRequest::count());
    }

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

    private function dosen(): User
    {
        $dosen = User::factory()->create(['role' => 'dosen']);

        AssessmentPeriod::forceCreate(['name' => 'Periode Uji', 'is_active' => true]);

        return $dosen;
    }

    /**
     * @return array{name: string, email: string, relationship: string}
     */
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

    private function oneInvitation(User $user, string $relationship): AssessmentRequest
    {
        $this->existingInvitations($user, $relationship, 1);

        return AssessmentRequest::where('user_id', $user->id)->firstOrFail();
    }
}
