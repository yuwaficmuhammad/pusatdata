<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    use ApiResponse;

    /**
     * Dapatkan history absensi untuk siswa yang sedang login.
     */
    public function history(Request $request)
    {
        $user = Auth::user();

        // Pastikan user adalah student
        if ($user->role !== 'student' || !$user->student) {
            return $this->errorResponse('Hanya siswa yang dapat mengakses riwayat presensi.', 403);
        }

        $limit = $request->input('limit', 30); // Default 30 hari terakhir

        $history = Attendance::where('student_id', $user->student->id)
            ->orderBy('date', 'desc')
            ->limit($limit)
            ->get();

        return $this->successResponse($history, 'Riwayat presensi berhasil diambil.');
    }

    /**
     * Update FCM Token perangkat Android (Untuk Push Notification)
     */
    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $user = Auth::user();
        $user->update([
            'fcm_token' => $request->fcm_token,
        ]);

        return $this->successResponse(null, 'Token FCM berhasil diperbarui.');
    }
}
