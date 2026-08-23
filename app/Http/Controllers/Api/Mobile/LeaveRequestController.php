<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LeaveRequest;
use Illuminate\Support\Facades\Storage;

class LeaveRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'student' || !$user->student) {
            return response()->json(['message' => 'Hanya siswa yang dapat mengakses fitur ini'], 403);
        }

        $requests = LeaveRequest::where('student_id', $user->student->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $requests
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'student' || !$user->student) {
            return response()->json(['message' => 'Hanya siswa yang dapat mengajukan izin'], 403);
        }

        $request->validate([
            'type' => 'required|in:sakit,izin',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:1000',
            'attachment' => 'nullable|image|max:2048', // Maks 2MB
        ]);

        $attachmentUrl = null;
        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('leave_attachments', 'public');
            $attachmentUrl = Storage::url($path);
        }

        $leaveRequest = LeaveRequest::create([
            'student_id' => $user->student->id,
            'type' => $request->type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'reason' => $request->reason,
            'attachment_url' => $attachmentUrl,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Pengajuan berhasil dikirim dan menunggu persetujuan',
            'data' => $leaveRequest
        ], 201);
    }
}
