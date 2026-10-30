<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\AssessmentResultController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\UserDashboardController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\JurusanController;
use App\Http\Controllers\UniversitySettingController;


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
    // (route dashboard dosen ada di group role:dosen di bawah, satu definisi saja)

    // Undang Penilai
    Route::post('/undang-penilai', [InvitationController::class, 'store'])->name('dashboard.invite');

    // Upload Portofolio
    Route::post('/upload-portofolio', [PortfolioController::class, 'store'])->name('portfolio.store');
    Route::get('/portofolio/{portfolio}/berkas', [PortfolioController::class, 'download'])->name('portfolio.file');

    // Proses Hitung & AI (tombol Proses)
    Route::post('/proses-hasil', [AssessmentResultController::class, 'generate'])->name('result.generate');

    // Halaman Hasil Akhir
    Route::get('/hasil-penilaian', [AssessmentResultController::class, 'show'])->name('result.show');

    // Route untuk batalkan undangan
    Route::delete('/cancel-invite/{assessmentRequest}', [InvitationController::class, 'destroy'])->name('invite.destroy');
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
    Route::get('/admin/settings', [UniversitySettingController::class, 'edit'])->name('admin.settings.edit');
    Route::put('/admin/settings', [UniversitySettingController::class, 'update'])->name('admin.settings.update');
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

    Route::get('/hasil-penilaian/pdf', [AssessmentResultController::class, 'pdf'])->name('result.pdf');
    // Tambahkan route lain untuk dosen sesuai kebutuhan (undang penilai, upload porto, proses hasil)...
});

// PENTING: Karena Anda pakai Laravel Breeze, pastikan route '/dashboard' default 
// diarahkan ke dashboard buatan kita atau biarkan user akses via menu.
// Untuk simpelnya, user bisa akses /dashboard-penilaian

// Route untuk Penilaian 360 (Akses via Token, tanpa Login)
Route::get('/nilai/{token}', [AssessmentController::class, 'show'])
    ->middleware('throttle:60,1')
    ->name('assessment.show');
Route::post('/nilai/{token}', [AssessmentController::class, 'store'])
    ->middleware('throttle:20,1')
    ->name('assessment.store');

// Halaman Status
Route::get('/penilaian-sukses', [AssessmentController::class, 'thankyou'])->name('assessment.thankyou');
Route::get('/penilaian-selesai', [AssessmentController::class, 'completed'])->name('assessment.completed');

require __DIR__.'/auth.php';
