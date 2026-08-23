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
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'date' => \Carbon\Carbon::parse($item->date)->translatedFormat('l, d F Y'),
                    'raw_date' => $item->date,
                    'time_in' => $item->time_in,
                    'time_out' => $item->time_out,
                    'status' => $item->status,
                    'late_minutes' => $item->late_minutes,
                ];
            });

        return $this->successResponse($history, 'Riwayat presensi berhasil diambil.');
    }

    /**
     * Dapatkan status presensi hari ini.
     */
    public function today(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'student' || !$user->student) {
            return $this->errorResponse('Akses ditolak.', 403);
        }

        $today = \Carbon\Carbon::now()->format('Y-m-d');
        $attendance = Attendance::where('student_id', $user->student->id)
            ->whereDate('date', $today)
            ->first();

        $data = null;
        if ($attendance) {
            $data = [
                'id' => $attendance->id,
                'date' => \Carbon\Carbon::parse($attendance->date)->translatedFormat('l, d F Y'),
                'time_in' => $attendance->time_in,
                'time_out' => $attendance->time_out,
                'status' => $attendance->status,
                'late_minutes' => $attendance->late_minutes,
            ];
        }

        return $this->successResponse($data, 'Status presensi hari ini.');
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
