<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LeaveRequest;
use App\Models\Attendance;
use Carbon\Carbon;

class LeaveRequestController extends Controller
{
    public function index()
    {
        $requests = LeaveRequest::with(['student.classrooms', 'approver'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);
            
        return view('leave_requests.index', compact('requests'));
    }

    public function approve(Request $request, $id)
    {
        $leaveRequest = LeaveRequest::findOrFail($id);
        
        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'Status pengajuan sudah tidak pending.');
        }

        $leaveRequest->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id
        ]);

        // Injeksi otomatis ke tabel attendance dari start_date sampai end_date
        $startDate = Carbon::parse($leaveRequest->start_date);
        $endDate = Carbon::parse($leaveRequest->end_date);
        
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            // Cek apakah bukan hari minggu (atau bisa cek via ScheduleService, namun asumsikan sederhana)
            if ($date->dayOfWeekIso === 7) {
                continue;
            }

            Attendance::updateOrCreate(
                [
                    'student_id' => $leaveRequest->student_id,
                    'date' => $date->format('Y-m-d')
                ],
                [
                    'status' => $leaveRequest->type,
                    'time_in' => null,
                    'time_out' => null,
                    'late_minutes' => 0,
                    'device_sn' => 'WEB_APPROVAL',
                    'schedule_version_id' => null
                ]
            );
        }

        return back()->with('success', 'Pengajuan berhasil disetujui dan absensi otomatis diperbarui.');
    }

    public function reject(Request $request, $id)
    {
        $leaveRequest = LeaveRequest::findOrFail($id);
        
        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'Status pengajuan sudah tidak pending.');
        }

        $leaveRequest->update([
            'status' => 'rejected',
            'approved_by' => $request->user()->id
        ]);

        return back()->with('success', 'Pengajuan ditolak.');
    }
}
