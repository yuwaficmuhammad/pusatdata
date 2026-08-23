<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Halaman Manajemen Jadwal (Dasar)
Route::get('/schedules', [\App\Http\Controllers\Web\ScheduleController::class, 'index'])->name('schedules.index');

// Halaman Laporan Presensi
Route::get('/attendance/reports', [\App\Http\Controllers\Web\AttendanceController::class, 'index'])->name('attendance.index');
