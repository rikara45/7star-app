<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\UserDashboardController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\JurusanController;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard'); 
})->middleware(['auth', 'verified'])->name('dashboard');

// Gabungkan semua route yang butuh auth di satu group
Route::middleware('auth')->group(function () {
    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Dashboard Utama
    Route::get('/dashboard-penilaian', [UserDashboardController::class, 'index'])->name('dashboard.index');
    
    // Undang Penilai
    Route::post('/undang-penilai', [UserDashboardController::class, 'invite'])->name('dashboard.invite');

    // Upload Portofolio
    Route::post('/upload-portofolio', [PortfolioController::class, 'store'])->name('portfolio.store');

    // Proses Hitung & AI (tombol Proses)
    Route::post('/proses-hasil', [UserDashboardController::class, 'generateResult'])->name('result.generate');

    // Halaman Hasil Akhir
    Route::get('/hasil-penilaian', [UserDashboardController::class, 'showResult'])->name('result.show');

    // Route untuk batalkan undangan
    Route::delete('/cancel-invite/{id}', [UserDashboardController::class, 'destroyRequest'])->name('invite.destroy');
});

// 1. Route Khusus ADMIN
Route::middleware(['auth', 'role:admin'])->group(function () {
    // Dashboard Utama (Statistik)
    Route::get('/admin/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
    
    // Manajemen User (CRUD Lengkap)
    Route::get('/admin/users', [AdminController::class, 'usersIndex'])->name('admin.users.index'); // Halaman Tabel
    Route::get('/admin/users/create', [AdminController::class, 'create'])->name('admin.users.create'); // Form Tambah
    Route::post('/admin/users', [AdminController::class, 'store'])->name('admin.users.store'); // Simpan Baru
    
    // Route Edit & Update (BARU)
    Route::get('/admin/users/{user}/edit', [AdminController::class, 'edit'])->name('admin.users.edit'); // Form Edit
    Route::put('/admin/users/{user}', [AdminController::class, 'update'])->name('admin.users.update'); // Simpan Perubahan
    
    Route::delete('/admin/users/{user}', [AdminController::class, 'destroy'])->name('admin.users.destroy'); // Hapus

    // Setting Universitas
    Route::get('/admin/settings', [App\Http\Controllers\UniversitySettingController::class, 'edit'])->name('admin.settings.edit');
    Route::put('/admin/settings', [App\Http\Controllers\UniversitySettingController::class, 'update'])->name('admin.settings.update');
});

// 2. Route Khusus JURUSAN (diperbarui)
Route::middleware(['auth', 'role:jurusan'])->group(function () {
    // Dashboard Statistik
    Route::get('/jurusan/dashboard', [JurusanController::class, 'dashboard'])->name('jurusan.dashboard');
    
    // Halaman List Verifikasi (Tabel)
    Route::get('/jurusan/verifikasi', [JurusanController::class, 'verificationIndex'])->name('jurusan.verifikasi.index');
    
    // Detail & Aksi
    Route::get('/jurusan/verifikasi/{userId}', [JurusanController::class, 'show'])->name('jurusan.verifikasi.show');
    Route::post('/jurusan/nilai/{portfolioId}', [JurusanController::class, 'storeScore'])->name('jurusan.nilai.store');

    Route::get('/jurusan/hasil/{userId}', [JurusanController::class, 'viewResult'])->name('jurusan.result');

    // ROUTE BARU: Download PDF
    Route::get('/jurusan/hasil/{userId}/pdf', [JurusanController::class, 'exportPdf'])->name('jurusan.result.pdf');
});

// 3. Route Khusus DOSEN (Target)
Route::middleware(['auth', 'role:dosen'])->group(function () {
    // Pindahkan atau tambahkan route dashboard dosen di sini jika ingin membatasi akses hanya untuk role 'dosen'
    Route::get('/dashboard-penilaian', [UserDashboardController::class, 'index'])->name('dashboard.index');

    Route::get('/hasil-penilaian/pdf', [UserDashboardController::class, 'exportPdf'])->name('result.pdf');
    // Tambahkan route lain untuk dosen sesuai kebutuhan (undang penilai, upload porto, proses hasil)...
});

// PENTING: Karena Anda pakai Laravel Breeze, pastikan route '/dashboard' default 
// diarahkan ke dashboard buatan kita atau biarkan user akses via menu.
// Untuk simpelnya, user bisa akses /dashboard-penilaian

// Route untuk Penilaian 360 (Akses via Token, tanpa Login)
Route::get('/nilai/{token}', [AssessmentController::class, 'show'])->name('assessment.show');
Route::post('/nilai/{token}', [AssessmentController::class, 'store'])->name('assessment.store');

// Halaman Status
Route::get('/penilaian-sukses', [AssessmentController::class, 'thankyou'])->name('assessment.thankyou');
Route::get('/penilaian-selesai', [AssessmentController::class, 'completed'])->name('assessment.completed');

// --- ROUTE TESTER (HANYA UNTUK MEMBUAT LINK UJI COBA) ---
// Hapus route ini nanti saat production
Route::get('/buat-link-test', function () {
    // Buat user dummy jika belum ada
    $user = \App\Models\User::firstOrCreate(
        ['email' => 'dosen@univ.ac.id'],
        ['name' => 'Dr. Budi Santoso', 'password' => bcrypt('password')]
    );

    // Buat request dummy
    $req = \App\Models\AssessmentRequest::create([
        'user_id' => $user->id,
        'assessment_period_id' => 1, // Pastikan seeder periode sudah jalan
        'assessor_name' => 'Prof. Atasan',
        'assessor_email' => 'atasan@univ.ac.id',
        'relationship' => 'superior',
        'access_token' => \Illuminate\Support\Str::random(32), // Generate Token
    ]);

    return "Link Test Anda: <a href='/nilai/{$req->access_token}'>Klik di sini untuk Menilai</a>";
});

require __DIR__.'/auth.php';
