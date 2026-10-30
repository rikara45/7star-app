# Penutupan Sisa Risiko — Irisan 6 dan 7

**Tujuan:** menutup tiga risiko yang tercatat tapi sengaja dulu tidak diubah: admin bisa
menurunkan role admin terakhir, undangan tanpa periode aktif berakhir 404, dan badge verifikasi
jurusan belum memakai komponen bersama.

**Spec terkait:** `2026-10-30-refactor-irisan-6-admin-pengaturan.md`,
`2026-10-30-refactor-irisan-7-tampilan.md`.

## 1. Admin terakhir tidak bisa diturunkan role-nya

- Aturan ada di `UserManagementService@update` (satu-satunya jalur update user), bukan di policy,
  karena policy tidak menerima role baru.
- Bila target ber-role `admin`, role baru bukan `admin`, dan tidak ada admin lain → `ValidationException`
  pada field `role` dengan pesan "Tidak bisa mengubah role admin terakhir. Tambahkan admin lain
  terlebih dahulu." Form admin menampilkan pesan itu seperti error validasi biasa.
- Menurunkan admin lain tetap boleh selama masih ada admin tersisa; admin yang mau turun sendiri juga
  boleh asal ada admin lain. Yang dijaga adalah sistem tetap punya minimal satu admin.
- Test: admin terakhir ditolak (role tidak berubah), admin lain bisa diturunkan saat masih ada dua
  admin, dan admin terakhir tetap bisa diubah nama/email tanpa menyentuh role.

## 2. Undangan tanpa periode aktif tidak lagi 404

- `InvitationController@store` memakai `first()` lalu `back()->with('error', 'Tidak ada periode aktif.')`,
  bukan `firstOrFail()`.
- Halaman `dashboard/no-period` menampilkan flash `error` supaya pesannya terlihat setelah redirect.
- Test: `InvitationTest` mengunci redirect + pesan + tidak ada undangan tersimpan (sebelumnya
  mengharapkan 404).

## 3. Badge verifikasi jurusan memakai `x-status-badge`

- `jurusan/verifikasi/index.blade.php`: badge "Belum Mengirim" (`red`), "n Perlu Verifikasi"
  (`yellow` + `pulse`), "Selesai" (`green`). Ikon dan titik tetap, hanya bungkusnya yang jadi komponen.
- `jurusan/verifikasi/detail.blade.php`: "Verified (Skor: n)" (`green`, mempertahankan `score-val`
  yang dipakai JavaScript), "Perlu Dinilai" (`yellow` + `pulse`), "Kosong" (`gray`).
- Emoji 🔔 dekoratif di badge "Perlu Dinilai" dihapus; statusnya sudah dibawa teks dan warna.
- String pengganti badge di JavaScript (setelah simpan nilai lewat AJAX) memakai kelas yang sama
  dengan komponen, jadi tampilan setelah simpan tidak kembali ke gaya lama.
- Perubahan tampilan yang disengaja: badge hijau jurusan memakai `bg-green-200` (sebelumnya
  `bg-green-100`), padding `px-2` (sebelumnya `px-3`), dan tanpa `shadow-sm` pada badge kuning.
- Test: `JurusanViewTest` mengunci tiga varian badge di daftar, tiga varian di detail, `score-val`
  yang dipertahankan, dan tidak adanya 🔔.

## Verifikasi

- `php artisan test` → 156 passed (576 assertions); Pint PASS 75 files.
- Mutasi yang dibuktikan memerahkan test: guard admin terakhir dihapus → 1 test gagal; `firstOrFail()`
  dikembalikan → 1 test gagal; badge hijau daftar dikembalikan ke `bg-green-100` → 1 test gagal.
- Browser (`php artisan serve --port=8089`):
  1. Sebagai jurusan: daftar verifikasi menampilkan "Belum Mengirim", "1 Perlu Verifikasi", "Selesai"
     dengan kelas komponen; halaman detail menampilkan "Verified (Skor: 7.5)", "Perlu Dinilai", dan
     "Kosong" tanpa 🔔.
  2. Sebagai dosen saat periode dinonaktifkan: dashboard menampilkan "Periode penilaian belum aktif";
     POST undangan kembali ke halaman itu dengan pesan "Tidak ada periode aktif." dan tidak ada
     undangan tersimpan. Periode lalu diaktifkan kembali.
- Data uji (3 user, portofolio, undangan) dihapus setelah verifikasi.

## Sisa risiko yang masih terbuka

- Admin masih bisa menurunkan role admin lain maupun dirinya selama masih ada admin lain; ini
  disengaja, bukan kelalaian.
- Throttle pada endpoint POST publik, `abort(403)` di dalam service, dan validasi sebelum resolusi
  token belum disentuh (tercatat di spec irisan 2).
