<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Halaman Manajemen Jadwal (Dasar)
Route::get('/schedules', [\App\Http\Controllers\Web\ScheduleController::class, 'index'])->name('schedules.index');

// Halaman Laporan Presensi
Route::prefix('attendance')->name('attendance.')->group(function () {
    Route::get('/reports', [\App\Http\Controllers\Web\AttendanceController::class, 'index'])->name('index');
    
    // Fitur Absensi Manual (Admin Only)
    // Asumsi: auth dan role:admin middleware sudah aktif di level aplikasi/controller, atau ditambahkan di sini
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/create', [\App\Http\Controllers\Web\AttendanceController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Web\AttendanceController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [\App\Http\Controllers\Web\AttendanceController::class, 'edit'])->name('edit');
        Route::put('/{id}', [\App\Http\Controllers\Web\AttendanceController::class, 'update'])->name('update');
    });
});
