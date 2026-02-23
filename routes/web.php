<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Feeder\CekMahasiswaController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\KalenderAkademikController;
use App\Http\Controllers\KegiatanMahasiswaController;
use App\Http\Controllers\LaporanPenerimaBeasiswaController;
use App\Http\Controllers\LembagaBeasiswaController;
use App\Http\Controllers\MahasiswaController;
use App\Http\Controllers\PenerimaBeasiswaController;
use App\Http\Controllers\PermissionsController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\SyncController;
use App\Http\Controllers\UsersController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login', 301);
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'checkPermission'])->group(function () {
    Route::get('/dashboard', [HomeController::class, 'index'])->name('dashboard');

    Route::post('mahasiswa/sync', [MahasiswaController::class, 'sync'])->name('mahasiswa.sync');
    Route::post('mahasiswa/detail/krs/{id}', [MahasiswaController::class, 'krs'])->name('mahasiswa.detail.krs');
    Route::post('mahasiswa/detail/khs/{id}', [MahasiswaController::class, 'khs'])->name('mahasiswa.detail.khs');
    Route::resource('mahasiswa', MahasiswaController::class);

    Route::resource('kalender-akademik', KalenderAkademikController::class)->except('show');
    Route::resource('kegiatan-mahasiswa', KegiatanMahasiswaController::class)->except('show');

    Route::resource('lembaga-beasiswa', LembagaBeasiswaController::class)->except('show');
    Route::resource('penerima-beasiswa', PenerimaBeasiswaController::class)->except('show');
    Route::resource('laporan-penerima-beasiswa', LaporanPenerimaBeasiswaController::class)->only('index');

    Route::resource('users', UsersController::class)->except('show');
    Route::resource('roles', RolesController::class)->except('show');
    Route::resource('permissions', PermissionsController::class)->only('index', 'create', 'store', 'destroy');

    Route::get('/master/sync/index', [SyncController::class, 'index'])->name('master.sync.index');
    Route::get('/master/sync/prodi', [SyncController::class, 'prodi'])->name('master.sync.prodi');
    Route::get('/master/sync/fakultas', [SyncController::class, 'fakultas'])->name('master.sync.fakultas');
    Route::get('/master/sync/jenis-matakuliah', [SyncController::class, 'jenisMataKuliah'])->name('master.sync.jenis-matakuliah');
    Route::get('/master/sync/kurikulum', [SyncController::class, 'kurikulum'])->name('master.sync.kurikulum');
    Route::get('/master/sync/kurikulum-prodi', [SyncController::class, 'kurikulumProdi'])->name('master.sync.kurikulum-prodi');
    Route::get('/master/sync/kurikulum-mata-kuliah', [SyncController::class, 'kurikulumMataKuliah'])->name('master.sync.kurikulum-mata-kuliah');
    Route::get('/master/sync/skala-nilai', [SyncController::class, 'skalaNilai'])->name('master.sync.skala-nilai');
    Route::get('/master/sync/krs', [SyncController::class, 'krs'])->name('master.sync.krs');
    Route::get('/master/sync/tahun-akademik', [SyncController::class, 'tahunAkademik'])->name('master.sync.tahun-akademik');
    Route::get('/master/sync/jadwal-perkuliahan', [SyncController::class, 'jadwalPerkuliahan'])->name('master.sync.jadwal-perkuliahan');
    Route::get('/master/sync/penerima-beasiswa', [SyncController::class, 'penerimaBeasiswa'])->name('master.sync.penerima-beasiswa');
    Route::get('/master/sync/jadwal-pertemuan', [SyncController::class, 'jadwalPertemuan'])->name('master.sync.jadwal-pertemuan');
    Route::get('/master/sync/jadwal-pertemuan-absensi', [SyncController::class, 'jadwalPertemuanAbsensi'])->name('master.sync.jadwal-pertemuan-absensi');
});

Route::prefix('neo-feeder')->name('neo-feeder.')->group(function () {
    Route::get('cek-krs', [CekMahasiswaController::class, 'cekKrs'])->name('cekkrs');
});
