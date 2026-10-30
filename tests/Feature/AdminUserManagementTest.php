<?php

namespace Tests\Feature;

use App\Http\Controllers\AdminController;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_melihat_daftar_user_dan_filter_role(): void
    {
        $admin = $this->admin();
        $dosen = User::factory()->create(['role' => 'dosen', 'name' => 'Dosen Filter']);
        $jurusan = User::factory()->create(['role' => 'jurusan', 'name' => 'Jurusan Filter']);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Dosen Filter')
            ->assertSee('Jurusan Filter');

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['roles' => ['dosen']]))
            ->assertOk()
            ->assertSee('Dosen Filter')
            ->assertDontSee('Jurusan Filter');
    }

    public function test_admin_menambah_user_dan_password_ter_hash(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Dosen Baru',
                'email' => 'dosen.baru@example.test',
                'password' => 'rahasia123',
                'role' => 'dosen',
                'jabatan' => 'Dosen Tetap',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success', 'User berhasil ditambahkan!');

        $user = User::where('email', 'dosen.baru@example.test')->firstOrFail();

        $this->assertSame('Dosen Baru', $user->name);
        $this->assertSame('dosen', $user->role);
        $this->assertSame('Dosen Tetap', $user->jabatan);
        $this->assertTrue(Hash::check('rahasia123', $user->password), 'Password harus tersimpan sebagai hash.');
    }

    public function test_email_duplikat_ditolak(): void
    {
        $admin = $this->admin();
        $existing = User::factory()->create(['email' => 'sudah.ada@example.test']);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Duplikat',
                'email' => $existing->email,
                'password' => 'rahasia123',
                'role' => 'dosen',
            ])
            ->assertSessionHasErrors('email');

        $this->assertSame(1, User::where('email', $existing->email)->count());
    }

    public function test_role_tidak_dikenal_ditolak(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Role Aneh',
                'email' => 'role.aneh@example.test',
                'password' => 'rahasia123',
                'role' => 'rektor',
            ])
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'role.aneh@example.test']);
    }

    public function test_password_kurang_dari_enam_ditolak(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Password Pendek',
                'email' => 'pendek@example.test',
                'password' => '12345',
                'role' => 'dosen',
            ])
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'pendek@example.test']);
    }

    public function test_admin_update_tanpa_mengganti_password(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['role' => 'dosen', 'password' => Hash::make('password')]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => 'Nama Diperbarui',
                'email' => $user->email,
                'role' => 'jurusan',
                'jabatan' => 'Kaprodi',
                'password' => '',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success', 'Data user berhasil diperbarui!');

        $user->refresh();

        $this->assertSame('Nama Diperbarui', $user->name);
        $this->assertSame('jurusan', $user->role);
        $this->assertSame('Kaprodi', $user->jabatan);
        $this->assertTrue(Hash::check('password', $user->password), 'Password lama tidak boleh berubah.');
    }

    public function test_admin_update_dengan_password_baru(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['role' => 'dosen', 'password' => Hash::make('password')]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'dosen',
                'password' => 'baru12345',
            ])
            ->assertRedirect(route('admin.users.index'));

        $hash = $user->fresh()->password;

        $this->assertTrue(Hash::check('baru12345', $hash));
        $this->assertFalse(Hash::check('password', $hash));
    }

    public function test_email_sendiri_tidak_dianggap_duplikat_saat_update(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['role' => 'dosen']);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => 'Nama Sama Email',
                'email' => $user->email,
                'role' => 'dosen',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.users.index'));

        $this->assertSame('Nama Sama Email', $user->fresh()->name);
    }

    public function test_admin_menghapus_user(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['role' => 'dosen']);

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->delete(route('admin.users.destroy', $user))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success', 'User berhasil dihapus.');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_tidak_bisa_menghapus_dirinya_sendiri(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->delete(route('admin.users.destroy', $admin))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'role' => 'admin']);
    }

    public function test_admin_bisa_menghapus_admin_lain(): void
    {
        $admin = $this->admin();
        $lain = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->delete(route('admin.users.destroy', $lain))
            ->assertSessionHas('success', 'User berhasil dihapus.');

        $this->assertDatabaseMissing('users', ['id' => $lain->id]);
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'role' => 'admin']);
    }

    public function test_non_admin_ditolak_untuk_semua_aksi_admin(): void
    {
        $target = User::factory()->create(['role' => 'dosen']);

        foreach (['dosen', 'jurusan'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.users.create'))->assertForbidden();
            $this->actingAs($user)->post(route('admin.users.store'), [
                'name' => 'Tidak Boleh',
                'email' => $role.'.tidak@example.test',
                'password' => 'rahasia123',
                'role' => 'admin',
            ])->assertForbidden();
            $this->actingAs($user)->get(route('admin.users.edit', $target))->assertForbidden();
            $this->actingAs($user)->put(route('admin.users.update', $target), [
                'name' => 'Tidak Boleh',
                'email' => $target->email,
                'role' => 'admin',
            ])->assertForbidden();
            $this->actingAs($user)->delete(route('admin.users.destroy', $target))->assertForbidden();
            $this->actingAs($user)->get(route('admin.settings.edit'))->assertForbidden();
            $this->actingAs($user)->put(route('admin.settings.update'), ['uni_name' => 'Tidak Boleh'])->assertForbidden();
        }

        $this->assertDatabaseHas('users', ['id' => $target->id, 'role' => 'dosen']);
        $this->assertDatabaseMissing('users', ['email' => 'dosen.tidak@example.test']);
    }

    public function test_jabatan_terlalu_panjang_ditolak(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Jabatan Panjang',
                'email' => 'jabatan.panjang@example.test',
                'password' => 'rahasia123',
                'role' => 'dosen',
                'jabatan' => str_repeat('a', 256),
            ])
            ->assertSessionHasErrors('jabatan');

        $this->assertDatabaseMissing('users', ['email' => 'jabatan.panjang@example.test']);
    }

    public function test_policy_hanya_mengizinkan_admin(): void
    {
        $admin = $this->admin();
        $dosen = User::factory()->create(['role' => 'dosen']);
        $lain = User::factory()->create(['role' => 'dosen']);

        $this->assertTrue(Gate::forUser($admin)->allows('manage', User::class));
        $this->assertTrue(Gate::forUser($dosen)->denies('manage', User::class));
        $this->assertTrue(Gate::forUser($admin)->allows('delete', $lain));
        $this->assertTrue(Gate::forUser($admin)->denies('delete', $admin));
        $this->assertTrue(Gate::forUser($dosen)->denies('delete', $lain));
    }

    public function test_email_milik_orang_lain_ditolak_saat_update(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['role' => 'dosen', 'email' => 'milik.sendiri@example.test']);
        $lain = User::factory()->create(['role' => 'dosen', 'email' => 'milik.orang.lain@example.test']);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => $lain->email,
                'role' => 'dosen',
            ])
            ->assertSessionHasErrors('email');

        $this->assertSame('milik.sendiri@example.test', $user->fresh()->email);
    }

    public function test_password_kurang_dari_enam_ditolak_saat_update(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['role' => 'dosen', 'password' => Hash::make('password')]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'dosen',
                'password' => '12345',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password), 'Password lama tidak boleh berubah.');
    }

    public function test_policy_menolak_pemanggilan_controller_tanpa_middleware(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);

        $this->actingAs($dosen);

        // Route asli dijaga middleware role:admin; ini membuktikan lapisan authorize
        // di controller tetap menolak bila middleware dilepas.
        $this->expectException(AuthorizationException::class);
        app(AdminController::class)->usersIndex(Request::create('/admin/users', 'GET'));
    }

    public function test_admin_terakhir_tidak_bisa_diturunkan_menjadi_dosen(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->put(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'dosen',
            ])
            ->assertSessionHasErrors('role');

        $this->assertSame('admin', $admin->fresh()->role, 'Admin terakhir tidak boleh kehilangan akses.');
    }

    public function test_admin_bisa_diturunkan_bila_masih_ada_admin_lain(): void
    {
        $admin = $this->admin();
        $lain = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $lain), [
                'name' => $lain->name,
                'email' => $lain->email,
                'role' => 'dosen',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertSame('dosen', $lain->fresh()->role);
    }

    public function test_admin_terakhir_tetap_bisa_diperbarui_tanpa_mengubah_role(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('admin.users.update', $admin), [
                'name' => 'Admin Diperbarui',
                'email' => $admin->email,
                'role' => 'admin',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertSame('Admin Diperbarui', $admin->fresh()->name);
        $this->assertSame('admin', $admin->fresh()->role);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }
}
