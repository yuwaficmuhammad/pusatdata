<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);

    // ADMS Webhook Endpoint (Tidak menggunakan Bearer token, auth berbasis SN)
    Route::match(['get', 'post'], '/attendance/push', [\App\Http\Controllers\Api\AttendanceController::class, 'push']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        
        // Master Data
        Route::apiResource('students', \App\Http\Controllers\Api\StudentController::class);
        Route::apiResource('classrooms', \App\Http\Controllers\Api\ClassroomController::class);

        // Schedule API
        Route::prefix('schedules')->group(function () {
            Route::get('/today', [\App\Http\Controllers\Api\ScheduleController::class, 'today']);
        });
    });
});
