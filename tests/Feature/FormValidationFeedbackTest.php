<?php

namespace Tests\Feature;

use App\Models\AssessmentPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Validasi yang gagal harus terlihat di form. Sebelumnya user kembali ke halaman
 * kosong tanpa pesan dan kehilangan seluruh isian.
 */
class FormValidationFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_form_undangan_menampilkan_error_dan_mempertahankan_isian(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        AssessmentPeriod::create(['name' => 'Periode Uji', 'is_active' => true]);

        $this->actingAs($dosen)
            ->from(route('dashboard.index'))
            ->post(route('dashboard.invite'), [
                'name' => 'Penilai Lama',
                'email' => 'bukan-email',
                'relationship' => 'peer',
            ])
            ->assertSessionHasErrors('email');

        $this->actingAs($dosen)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('Format email penilai tidak valid.')
            ->assertSee('value="Penilai Lama"', false);
    }

    public function test_form_tambah_pengguna_menampilkan_error_dan_mempertahankan_isian(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'name' => '',
                'email' => 'bukan-email',
                'password' => 'rahasia123',
                'role' => 'dosen',
            ])
            ->assertSessionHasErrors(['name', 'email']);

        $this->actingAs($admin)
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('Nama pengguna wajib diisi.')
            ->assertSee('Format email tidak valid.')
            ->assertSee('value="bukan-email"', false);
    }

    public function test_form_edit_pengguna_menampilkan_error_dan_mempertahankan_isian(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['role' => 'dosen', 'name' => 'Nama Asli']);

        $this->actingAs($admin)
            ->from(route('admin.users.edit', $target->id))
            ->put(route('admin.users.update', $target->id), [
                'name' => 'Nama Baru',
                'email' => 'bukan-email',
                'role' => 'dosen',
            ])
            ->assertSessionHasErrors('email');

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $target->id))
            ->assertOk()
            ->assertSee('Format email tidak valid.')
            ->assertSee('value="Nama Baru"', false)
            ->assertSee('value="bukan-email"', false);
    }

    public function test_form_pengaturan_menampilkan_error_dan_mempertahankan_isian(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('admin.settings.edit'))
            ->put(route('admin.settings.update'), [
                'uni_name' => '',
                'uni_address' => 'Alamat Lama',
            ])
            ->assertSessionHasErrors('uni_name');

        $this->actingAs($admin)
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('Nama universitas wajib diisi.')
            ->assertSee('Alamat Lama');
    }
}
