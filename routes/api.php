<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);

    // ADMS Webhook Endpoint (Tidak menggunakan Bearer token, auth berbasis SN)
    Route::match(['get', 'post'], '/attendance/push', [\App\Http\Controllers\Api\AttendanceController::class, 'push'])->middleware('throttle:adms');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
        Route::get('/auth/me', [\App\Http\Controllers\Api\AuthController::class, 'me']);

        // Sinkronisasi Manual ADMS (Upload File)
        Route::post('/attendance/sync-manual', [\App\Http\Controllers\Api\AttendanceController::class, 'syncManual']);
        
        // Mobile App Endpoints
        Route::prefix('mobile')->group(function () {
            Route::get('/attendance/today', [\App\Http\Controllers\Api\Mobile\AttendanceController::class, 'today']);
            Route::get('/attendance/history', [\App\Http\Controllers\Api\Mobile\AttendanceController::class, 'history']);
            Route::post('/fcm-token', [\App\Http\Controllers\Api\Mobile\AttendanceController::class, 'updateFcmToken']);
        });
        
        // Master Data
        Route::apiResource('students', \App\Http\Controllers\Api\StudentController::class);
        Route::apiResource('classrooms', \App\Http\Controllers\Api\ClassroomController::class);

        // Schedule API
        Route::prefix('schedules')->group(function () {
            Route::get('/today', [\App\Http\Controllers\Api\ScheduleController::class, 'today']);
        });
    });
});
