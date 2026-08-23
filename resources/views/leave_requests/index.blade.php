<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Pengajuan Izin/Sakit</title>
    <!-- Anda bisa menyertakan CSS framework yang digunakan, contoh: Tailwind atau Bootstrap -->
    <style>
        body { font-family: sans-serif; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; }
        .btn { padding: 5px 10px; margin-right: 5px; cursor: pointer; border: none; border-radius: 3px; color: white; }
        .btn-approve { background-color: #28a745; }
        .btn-reject { background-color: #dc3545; }
        .badge-pending { background: #ffc107; padding: 3px 6px; border-radius: 4px; font-size: 12px; }
        .badge-approved { background: #28a745; color: white; padding: 3px 6px; border-radius: 4px; font-size: 12px; }
        .badge-rejected { background: #dc3545; color: white; padding: 3px 6px; border-radius: 4px; font-size: 12px; }
    </style>
</head>
<body>
    <h2>Pengajuan Izin / Sakit</h2>
    
    @if(session('success'))
        <div style="color: green; margin-bottom: 15px;">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div style="color: red; margin-bottom: 15px;">{{ session('error') }}</div>
    @endif

    <table>
        <thead>
            <tr>
                <th>Siswa</th>
                <th>Tipe</th>
                <th>Tanggal</th>
                <th>Alasan</th>
                <th>Lampiran</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($requests as $request)
            <tr>
                <td>{{ $request->student->name }}</td>
                <td>{{ ucfirst($request->type) }}</td>
                <td>{{ \Carbon\Carbon::parse($request->start_date)->format('d M Y') }} - {{ \Carbon\Carbon::parse($request->end_date)->format('d M Y') }}</td>
                <td>{{ $request->reason }}</td>
                <td>
                    @if($request->attachment_url)
                        <a href="{{ $request->attachment_url }}" target="_blank">Lihat</a>
                    @else
                        -
                    @endif
                </td>
                <td>
                    <span class="badge-{{ $request->status }}">{{ ucfirst($request->status) }}</span>
                </td>
                <td>
                    @if($request->status === 'pending')
                        <form action="{{ url('/leave-requests/'.$request->id.'/approve') }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-approve" onclick="return confirm('Yakin menyetujui?')">Approve</button>
                        </form>
                        <form action="{{ url('/leave-requests/'.$request->id.'/reject') }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-reject" onclick="return confirm('Yakin menolak?')">Reject</button>
                        </form>
                    @else
                        Oleh: {{ $request->approver ? $request->approver->name : '-' }}
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
