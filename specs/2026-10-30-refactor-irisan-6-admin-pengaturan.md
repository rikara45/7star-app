# Rencana Irisan 6 — Admin dan Pengaturan Universitas

> **Untuk pelaksana:** gunakan skill `superpowers:executing-plans` (eksekusi inline di sesi ini,
> sesuai pilihan pemilik repo).

**Tujuan:** memindahkan aturan CRUD user admin dan update pengaturan universitas keluar dari
controller ke FormRequest + service + policy, tanpa mengubah perilaku yang dilihat pengguna.

**Arsitektur:** `UserManagementService` memegang pembuatan/perubahan user (hash password);
`StoreUserRequest`/`UpdateUserRequest`/`UpdateUniversitySettingRequest` memegang validasi;
`UserPolicy`/`UniversitySettingPolicy` menjaga aksi langsung di luar middleware; controller tinggal
orkestrasi HTTP.

**Stack:** Laravel 12, Blade, PHPUnit feature test, SQLite in-memory.

**Spec induk:** `specs/2026-10-30-refactor-arsitektur-design.md` bagian "Irisan 6".

## Batasan Global

- Tidak menambah dependency; tidak memakai queue.
- Nama route `admin.*` dan URL tidak berubah.
- Pesan sukses/error yang sudah ada dipertahankan bunyinya.
- `tests/Feature/ScoringCharacterizationTest.php` tidak disentuh.
- `php artisan test` hijau dan `php vendor/bin/pint --test app tests` PASS setiap task.
- Tidak ada `env()` di luar `config/`; tidak ada `DB::table()` di controller.

## Perilaku yang harus tetap sama

| Kasus | Perilaku sekarang |
| --- | --- |
| Filter `?roles[]=dosen&roles[]=admin` | daftar user difilter `whereIn('role', ...)` |
| Tambah user valid | user dibuat, redirect `admin.users.index`, `User berhasil ditambahkan!` |
| Email sudah dipakai | error validasi `email` |
| Password < 6 karakter | error validasi `password` |
| Role di luar `admin,jurusan,dosen` | error validasi `role` |
| Update tanpa password | password lama tidak berubah |
| Update dengan password | password baru dipakai |
| Email sendiri saat update | tidak dianggap duplikat |
| Hapus user | baris dihapus, `back()`, `User berhasil dihapus.` |
| Update pengaturan valid | `Profil Universitas berhasil diperbarui!` |
| Logo baru | logo lama dihapus dari disk `public`, logo baru disimpan di `settings/` |
| Logo bukan png/jpg/jpeg atau > 2 MB | error validasi `uni_logo` |
| Non-admin membuka route admin | `403` (middleware `role:admin`) |

Perubahan yang disengaja (dicatat):
- `jabatan` kini `nullable|string|max:255` dan `name` `max:255` (dulu tanpa batas → 500 dari kolom
  `varchar(255)` bila terlalu panjang).
- `uni_address` `nullable|string|max:5000` (kolom `text`), `uni_website` `nullable|string|max:255`.
  `uni_website` **tidak** memakai aturan `url` supaya nilai lama seperti `www.kampus.ac.id` tidak
  tiba-tiba memblokir penyimpanan.
- `UniversitySetting` diambil dengan `firstOrCreate([], ['uni_name' => ...])` supaya tidak 500 bila baris
  tunggal hilang; default diisi eksplisit agar form pertama langsung menampilkan nama, bukan kosong.
- Policy dipakai sebagai lapisan kedua: route tetap dijaga middleware `role:admin`, policy menjaga
  pemanggilan langsung. `authorize` dijalankan **sebelum** `firstOrCreate` (ability `manage`) supaya
  non-admin tidak memicu penulisan baris.
- Password saat **update** kini `nullable|string|min:6` (dulu tanpa batas). Ini perubahan perilaku yang
  disengaja agar konsisten dengan pembuatan user; password lama tidak berubah bila validasi gagal.
- Admin **tidak bisa menghapus akunnya sendiri** (`UserPolicy@delete`). View memang sudah menyembunyikan
  tombolnya, tapi route langsung dulu tetap menghapus; di shared hosting tanpa SSH, kehilangan admin
  terakhir berarti pemulihan lewat database. Menghapus admin lain tetap boleh.

Di luar cakupan (risiko dicatat, bukan diubah di irisan ini):
- Admin masih bisa menurunkan role dirinya sendiri menjadi `dosen` lewat edit, sehingga tidak ada admin
  tersisa. Penjagaan serupa untuk `update` belum dibuat.
- Hapus user tetap menghapus data terkait lewat cascade (portofolio, undangan, analisis); konfirmasi
  di view sudah menyebut "beserta seluruh datanya".
- `unit_kerja` tidak ada di form admin, jadi tidak diubah.
- Role masih `string` di DB; validasi adalah satu-satunya penjaga.

---

### Task 0: Branch

```powershell
git switch main
git switch -c refactor/admin-settings
```

Kriteria: `git status --short` hanya `.opencode/`, `AGENTS.md`, `logs/`.

---

### Task 1: FormRequest, service, policy, dan test-nya

**Files:**
- Create: `app/Http/Requests/StoreUserRequest.php`
- Create: `app/Http/Requests/UpdateUserRequest.php`
- Create: `app/Http/Requests/UpdateUniversitySettingRequest.php`
- Create: `app/Services/UserManagementService.php`
- Create: `app/Policies/UserPolicy.php`
- Create: `app/Policies/UniversitySettingPolicy.php`
- Test: `tests/Feature/AdminUserManagementTest.php`
- Test: `tests/Feature/UniversitySettingTest.php`

**Interfaces:**
- `UserManagementService::create(array $data): User`
- `UserManagementService::update(User $user, array $data): User`
  (keduanya memakai `Hash::make`; cast `hashed` di model tidak double-hash karena Laravel memeriksa
  `Hash::isHashed`.)
- `UserPolicy::manage(User $user): bool` → `role === 'admin'`
- `UniversitySettingPolicy::update(User $user, UniversitySetting $setting): bool` → `role === 'admin'`
- `StoreUserRequest`/`UpdateUserRequest` rules memakai `Rule::unique('users')->ignore(...)`.

- [ ] **Step 1: Tulis test yang gagal**

Buat `AdminUserManagementTest` dan `UniversitySettingTest` lebih dulu (lihat daftar test di bawah),
jalankan `php artisan test --filter="AdminUserManagementTest|UniversitySettingTest"` dan pastikan
gagal pada hal yang benar (class FormRequest/policy belum ada, atau validasi belum menolak).

Daftar test minimal:

`AdminUserManagementTest`
1. `test_admin_melihat_daftar_user_dan_filter_role`
2. `test_admin_menambah_user_dan_password_ter_hash`
3. `test_email_duplikat_ditolak`
4. `test_role_tidak_dikenal_ditolak`
5. `test_password_kurang_dari_enam_ditolak`
6. `test_admin_update_tanpa_mengganti_password`
7. `test_admin_update_dengan_password_baru`
8. `test_email_sendiri_tidak_dianggap_duplikat_saat_update`
9. `test_admin_menghapus_user`
10. `test_non_admin_ditolak_untuk_semua_aksi_admin`
11. `test_jabatan_terlalu_panjang_ditolak`
12. `test_policy_hanya_mengizinkan_admin`

`UniversitySettingTest`
1. `test_admin_melihat_form_pengaturan`
2. `test_admin_menyimpan_pengaturan`
3. `test_nama_universitas_wajib_diisi`
4. `test_nama_universitas_terlalu_panjang_ditolak`
5. `test_alamat_dan_website_terlalu_panjang_ditolak`
6. `test_logo_baru_menggantikan_logo_lama`
7. `test_logo_bukan_gambar_ditolak`
8. `test_non_admin_ditolak_mengubah_pengaturan`
9. `test_policy_pengaturan_hanya_admin`

- [ ] **Step 2: Jalankan test, pastikan gagal**

```powershell
php artisan test --filter="AdminUserManagementTest|UniversitySettingTest"
```

- [ ] **Step 3: Buat `StoreUserRequest`**

```php
public function rules(): array
{
    return [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        'password' => ['required', 'string', 'min:6'],
        'role' => ['required', 'in:admin,jurusan,dosen'],
        'jabatan' => ['nullable', 'string', 'max:255'],
    ];
}
```

- [ ] **Step 4: Buat `UpdateUserRequest`**

```php
'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user'))],
'password' => ['nullable', 'string', 'min:6'],
```

- [ ] **Step 5: Buat `UpdateUniversitySettingRequest`**

```php
'uni_name' => ['required', 'string', 'max:255'],
'uni_address' => ['nullable', 'string', 'max:5000'],
'uni_website' => ['nullable', 'string', 'max:255'],
'uni_logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
```

- [ ] **Step 6: Buat `UserManagementService` dan dua policy**

- [ ] **Step 7: Jalankan test, pastikan lulus; Pint**

- [ ] **Step 8: Commit** `refactor(admin): validasi dan service manajemen user`

---

### Task 2: Controller tipis, route, dan pemakaian policy

**Files:**
- Modify: `app/Http/Controllers/AdminController.php`
- Modify: `app/Http/Controllers/UniversitySettingController.php`
- Modify: `routes/web.php`

- [ ] **Step 1: `AdminController`**

Setiap aksi mutasi memanggil `$this->authorize('manage', User::class)` lebih dulu, lalu memakai
`StoreUserRequest`/`UpdateUserRequest` + `UserManagementService`. `usersIndex` tetap memakai filter
`roles[]` (dipindah ke private method `filterByRoles` bila perlu). Target < 100 baris.

- [ ] **Step 2: `UniversitySettingController`**

`edit()` memakai `UniversitySetting::firstOrCreate([])`; `update()` memanggil
`$this->authorize('update', $setting)` lalu `UpdateUniversitySettingRequest`; logika logo pindah ke
private method `replaceLogo()` yang hanya menyentuh `Storage`, tetap di controller karena murni
penyimpanan berkas. Target < 70 baris.

- [ ] **Step 3: Route**

Ganti FQCN `App\Http\Controllers\UniversitySettingController` dengan import biasa di
`routes/web.php`. Nama route dan URL tidak berubah.

- [ ] **Step 4: Verifikasi**

```powershell
php artisan test
php vendor/bin/pint --test app tests
php artisan route:list --name=admin
php artisan route:list --path=admin/settings
```

Harapan: suite hijau (116 + 21 test baru), Pint PASS, route `admin.*` dan `admin.settings.*` utuh.

- [ ] **Step 5: Commit** `refactor(admin): controller tipis untuk user dan pengaturan`

---

### Task 3: Verifikasi nyata dan merge

- [ ] **Step 1: Cek statis** (`php artisan test`, `pint --test`, `route:list`)
- [ ] **Step 2: Browser** (`php artisan serve --port=8087`, login admin):
  1. Buat user dosen baru → muncul di daftar.
  2. Edit user: ganti jabatan tanpa password → tersimpan; password lama masih bisa dipakai login.
  3. Filter `?roles[]=dosen` → hanya dosen.
  4. Hapus user uji.
  5. Update pengaturan: ubah nama universitas, unggah logo kecil → tersimpan dan logo tampil.
  6. Login sebagai dosen lalu `GET /admin/users` → 403.
- [ ] **Step 3: Merge** `git switch main && git merge --no-ff refactor/admin-settings`
- [ ] **Step 4: Push** hanya setelah dikonfirmasi pemilik repo.

## Definition of Done

- `AdminController` < 100 baris dan tidak memanggil `Hash::make`/validasi langsung.
- `UniversitySettingController` < 70 baris, memakai FormRequest + policy.
- `UserManagementService` satu-satunya tempat pembuatan/perubahan user.
- Policy `UserPolicy`/`UniversitySettingPolicy` benar-benar terdaftar (dibuktikan `Gate::forUser`).
- Test mencakup CRUD admin, non-admin 403, role salah, password pendek, email duplikat, update tanpa
  password, logo baru menggantikan lama, dan validasi pengaturan.
- Suite penuh hijau, Pint PASS.
