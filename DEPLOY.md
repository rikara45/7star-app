# Deploy 7-Star Assessment ke Shared Hosting

Panduan ini ditulis untuk cPanel tanpa akses root, tanpa Docker, dan tanpa queue worker.

## 1. Docroot

Cara paling aman: arahkan domain/subdomain ke folder `public/` (bukan root repo). Di cPanel:
**Domains → Manage → Document Root** lalu isi `public`.

Bila hosting tidak mengizinkan perubahan docroot:

- Pastikan file `.htaccess` di root repo ikut terunggah.
- File itu menulis ulang request ke `public/` (aman untuk instalasi subdirektori), menolak akses ke
  dotfile seperti `.env` dan `.git`, serta memblokir `database/`, `storage/`, `vendor/`,
  `node_modules/`, dan file `*.sqlite`, `*.log`, `*.zip`, `*.bak`. Folder `.well-known` tetap
  dibuka agar perpanjangan SSL otomatis tidak gagal.
- Setelah deploy, cek `https://domain-anda/.env` dan `https://domain-anda/.git/config` harus 403
  atau 404, bukan berisi teks.

## 2. Yang diunggah

- Seluruh kode aplikasi, termasuk `vendor/` bila tidak ada Composer di hosting.
- `public/build/` **wajib ikut**, karena hosting tanpa Node tidak bisa menjalankan `npm run build`.
- Jangan pernah mengunggah `*.zip`, `node_modules/`, `database/*.sqlite`, atau file `.env` lokal ke folder publik.

## 3. Environment produksi

Buat `.env` di server (jangan commit). Nilai minimum:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-anda
APP_TIMEZONE=Asia/Jakarta

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=nama_database
DB_USERNAME=user_database
DB_PASSWORD=password_database

MAIL_MAILER=smtp
MAIL_HOST=smtp.domain-anda
MAIL_PORT=587
MAIL_USERNAME=alamat@domain-anda
MAIL_PASSWORD=password_email
MAIL_FROM_ADDRESS=alamat@domain-anda
MAIL_FROM_NAME="7-Star Assessment"

LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=warning

GEMINI_API_KEY=key-baru-hasil-rotasi
```

Bila `APP_KEY` masih kosong, jalankan `php artisan key:generate --force`.

## 4. Rotasi key Gemini

Key lama yang pernah tertanam di `config/services.php` harus dianggap bocor.

1. Buka Google AI Studio, buat key baru.
2. Hapus/revoke key lama.
3. Isi key baru hanya di `.env` server dan `.env` lokal.
4. Jangan menaruh key di config yang di-commit.

## 5. Migrasi dan data awal

```
php artisan migrate --force
```

Tanpa `--seed`. Seeder tidak lagi menghapus pertanyaan atau jawaban, tetapi data awal tetap
sebaiknya dibuat eksplisit:

```
# isi SEED_ADMIN_EMAIL, SEED_ADMIN_PASSWORD, dan opsional SEED_JURUSAN_* di .env dulu
php artisan db:seed --force
```

Setelah akun admin dibuat, kosongkan `SEED_ADMIN_PASSWORD` di `.env`. Seeder hanya menolak password
literal `password` di `production`, jadi tetap gunakan password panjang dan unik. Bila kredensial
seeder kosong, seeder tidak membuat akun apa pun.

Bila database sudah berisi data, jangan jalankan `migrate:fresh`.

Catatan timezone: data lama tersimpan dalam UTC. Setelah `APP_TIMEZONE=Asia/Jakarta`, baris baru
memakai WIB. Backfill hanya perlu dilakukan bila tanggal lama memang harus tampil dalam WIB.

## 6. Storage dan izin

```
php artisan storage:link
```

Pastikan `public/storage` adalah symlink hasil perintah di atas, bukan folder salinan. Bila
sebelumnya berupa folder salinan, pindahkan isinya lalu hapus foldernya.

Portofolio **tidak boleh** disimpan di disk `public` (`storage/app/public/portfolios`), karena
symlink akan menyajikannya sebagai file statis tanpa cek policy. Unggahan baru memakai disk `local`,
dan `public/.htaccess` menolak `/storage/portfolios/` sebagai lapisan kedua.

Bila instalasi lama masih punya berkas di disk publik, pindahkan dulu ke disk privat:

```bash
mkdir -p storage/app/private/portfolios
mv storage/app/public/portfolios/* storage/app/private/portfolios/
rmdir storage/app/public/portfolios
```

Di Nginx (termasuk Coolify), tambahkan aturan yang sama karena `.htaccess` tidak dibaca:

```nginx
location ^~ /storage/portfolios/ { return 404; }
```

Setelah deploy, cek berkas portofolio yang sudah ada:

```bash
curl -I https://domain-anda/storage/portfolios/<nama-berkas>.pdf
```

Respons harus `404`. Bila masih `200`, berkas belum dipindahkan dari disk publik.

Pastikan folder berikut dapat ditulis oleh user web server:

- `storage/`
- `storage/framework/cache/`
- `storage/framework/sessions/`
- `storage/framework/views/`
- `storage/logs/`
- `bootstrap/cache/`

## 7. Cache produksi

```
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Setiap kali `.env` atau route berubah, jalankan `php artisan optimize:clear` lalu cache ulang.

## 8. Setelah deploy

- Buka `/` dan pastikan halaman tampil dengan CSS dari `public/build`.
- Login admin, buat akun dosen lewat menu admin (registrasi mandiri sudah ditutup).
- Cek `storage/logs/laravel.log` tidak berisi error.
- Pastikan `https://domain-anda/.env` tidak dapat diakses.
