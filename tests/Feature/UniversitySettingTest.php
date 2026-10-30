<?php

namespace Tests\Feature;

use App\Http\Controllers\UniversitySettingController;
use App\Models\UniversitySetting;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UniversitySettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_melihat_form_pengaturan(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('Universitas Default');
    }

    public function test_admin_menyimpan_pengaturan(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('admin.settings.edit'))
            ->put(route('admin.settings.update'), [
                'uni_name' => 'Universitas Nusantara',
                'uni_address' => 'Jl. Baru No. 2',
                'uni_website' => 'https://nusantara.ac.id',
            ])
            ->assertRedirect(route('admin.settings.edit'))
            ->assertSessionHas('success', 'Profil Universitas berhasil diperbarui!');

        $setting = UniversitySetting::firstOrFail();

        $this->assertSame('Universitas Nusantara', $setting->uni_name);
        $this->assertSame('Jl. Baru No. 2', $setting->uni_address);
        $this->assertSame('https://nusantara.ac.id', $setting->uni_website);
    }

    public function test_nama_universitas_wajib_diisi(): void
    {
        $admin = $this->admin();
        $sebelum = UniversitySetting::firstOrFail()->uni_name;

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), ['uni_name' => ''])
            ->assertSessionHasErrors('uni_name');

        $this->assertSame($sebelum, UniversitySetting::firstOrFail()->uni_name);
    }

    public function test_nama_universitas_terlalu_panjang_ditolak(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), ['uni_name' => str_repeat('a', 256)])
            ->assertSessionHasErrors('uni_name');
    }

    public function test_alamat_dan_website_terlalu_panjang_ditolak(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                'uni_name' => 'Universitas Uji',
                'uni_address' => str_repeat('a', 5001),
                'uni_website' => str_repeat('a', 256),
            ])
            ->assertSessionHasErrors(['uni_address', 'uni_website']);

        $this->assertSame('Universitas Default', UniversitySetting::firstOrFail()->uni_name);
    }

    public function test_logo_baru_menggantikan_logo_lama(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('settings/lama.png', 'gambar lama');

        $admin = $this->admin();
        $setting = UniversitySetting::firstOrFail();
        $setting->update(['uni_logo_path' => 'settings/lama.png']);

        $this->actingAs($admin)
            ->from(route('admin.settings.edit'))
            ->put(route('admin.settings.update'), [
                'uni_name' => 'Universitas Logo',
                'uni_logo' => UploadedFile::fake()->image('baru.png', 120, 120),
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $path = $setting->fresh()->uni_logo_path;

        $this->assertNotSame('settings/lama.png', $path);
        Storage::disk('public')->assertMissing('settings/lama.png');
        Storage::disk('public')->assertExists($path);
    }

    public function test_logo_bukan_gambar_ditolak(): void
    {
        Storage::fake('public');

        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                'uni_name' => 'Universitas Uji',
                'uni_logo' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('uni_logo');

        $this->assertNull(UniversitySetting::firstOrFail()->uni_logo_path);
        $this->assertSame('Universitas Default', UniversitySetting::firstOrFail()->uni_name);
    }

    public function test_non_admin_ditolak_mengubah_pengaturan(): void
    {
        foreach (['dosen', 'jurusan'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get(route('admin.settings.edit'))->assertForbidden();
            $this->actingAs($user)
                ->put(route('admin.settings.update'), ['uni_name' => 'Diubah Non Admin'])
                ->assertForbidden();
        }

        $this->assertSame('Universitas Default', UniversitySetting::firstOrFail()->uni_name);
    }

    public function test_policy_pengaturan_hanya_admin(): void
    {
        $admin = $this->admin();
        $dosen = User::factory()->create(['role' => 'dosen']);
        $setting = UniversitySetting::firstOrFail();

        $this->assertTrue(Gate::forUser($admin)->allows('update', $setting));
        $this->assertTrue(Gate::forUser($dosen)->denies('update', $setting));
        $this->assertTrue(Gate::forUser($admin)->allows('manage', UniversitySetting::class));
        $this->assertTrue(Gate::forUser($dosen)->denies('manage', UniversitySetting::class));
    }

    public function test_logo_lebih_dari_dua_mb_ditolak(): void
    {
        Storage::fake('public');

        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                'uni_name' => 'Universitas Uji',
                'uni_logo' => UploadedFile::fake()->image('besar.png', 300, 300)->size(3000),
            ])
            ->assertSessionHasErrors('uni_logo');

        $this->assertNull(UniversitySetting::firstOrFail()->uni_logo_path);
    }

    public function test_pengaturan_dibuat_saat_baris_tunggal_hilang(): void
    {
        UniversitySetting::query()->delete();

        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('Universitas Teknologi Indonesia');

        $this->assertSame(1, UniversitySetting::count());
    }

    public function test_policy_menolak_pemanggilan_controller_pengaturan_tanpa_middleware(): void
    {
        UniversitySetting::query()->delete();

        $dosen = User::factory()->create(['role' => 'dosen']);

        $this->actingAs($dosen);

        // Policy harus menolak sebelum firstOrCreate menulis baris apa pun.
        try {
            app(UniversitySettingController::class)->edit();
            $this->fail('Non-admin seharusnya ditolak policy.');
        } catch (AuthorizationException) {
            $this->assertSame(0, UniversitySetting::count(), 'Tidak boleh ada baris dibuat sebelum authorize.');
        }
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }
}
