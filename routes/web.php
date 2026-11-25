<?php

use App\Http\Controllers\SekolahController;
use App\Http\Controllers\ProfilSekolahController;
use App\Http\Controllers\ProfilSekolahPublikController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PetaController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\KataSandiController;
use App\Http\Controllers\ActivityLogController;
use Illuminate\Support\Facades\Route;

Route::controller(PetaController::class)->group(function () {
    Route::get('/', 'index')->name('peta.index');
    Route::get('/pencarian', 'cari')->name('peta.cari');
    Route::get('/klaster', 'klaster')->name('peta.klaster');
    Route::get('/peta-panas', 'panas')->name('peta.panas');
    Route::get('/wilayah', 'wilayah')->name('peta.wilayah');
    Route::get('/rute', 'rute')->name('peta.rute');
    Route::get('/cek-alamat', 'alamat')->name('peta.alamat');
    Route::get('/gabungan', 'gabungan')->name('peta.gabungan'); // TAMBAHAN
    Route::get('/jangkauan', 'jangkauan')->name('peta.jangkauan');
    Route::get('/data/sekolah', 'geojson')->name('peta.data');
    Route::get('/data/wilayah', 'geojsonWilayah')->name('peta.data.wilayah');
    Route::get('/data/sekolah/terdekat', [PetaController::class, 'terdekat']);
});

// Profil publik per sekolah (tanpa login), dibuka dari popup peta
Route::get('/profil-sekolah/{sekolah:npsn}', [ProfilSekolahPublikController::class, 'show'])
    ->name('publik.sekolah.show');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');
    Route::get('/dashboard', DashboardController::class)
        ->name('dashboard');
});

Route::middleware(['auth', 'active', 'role:dinas'])
    ->prefix('admin')->name('dinas.')->group(function () {
        Route::resource('sekolah', SekolahController::class)
            ->only(['index', 'create', 'store', 'destroy']);
    });

// Admin: kelola pengguna
Route::middleware(['auth', 'active', 'role:admin'])
    ->prefix('admin')->name('admin.')
    ->group(function () {
        Route::resource('users', UserController::class)->except('show');
    });

Route::middleware(['auth', 'active', 'role:dinas,sekolah'])->group(function () {
    Route::get('/sekolah/{sekolah}/edit', [SekolahController::class, 'edit'])
        ->name('sekolah.edit');
    Route::put('/sekolah/{sekolah}', [SekolahController::class, 'update'])
        ->name('sekolah.update');

    Route::get('sekolah/{sekolah}/profil',
        [ProfilSekolahController::class, 'edit'])->name('sekolah.profil.edit');
    Route::put('sekolah/{sekolah}/profil',
        [ProfilSekolahController::class, 'update'])->name('sekolah.profil.update');
});

// Ubah kata sandi (semua pengguna yang login)
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/kata-sandi', [KataSandiController::class, 'edit'])
        ->name('kata-sandi.edit');
    Route::put('/kata-sandi', [KataSandiController::class, 'update'])
        ->name('kata-sandi.update');
});

// Aktif/nonaktif akun dan log aktivitas (khusus admin)
Route::middleware(['auth', 'active', 'role:admin'])->prefix('admin')->name('admin.')
    ->group(function () {
        Route::patch('users/{user}/aktif', [UserController::class, 'toggleAktif'])
            ->name('users.aktif');
        Route::get('log-aktivitas', [ActivityLogController::class, 'index'])
            ->name('activity-logs.index');
    });