@extends('layouts.app')

@section('title', 'Input Presensi Manual')

@section('content')
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Input Presensi Manual</h2>
            <p class="text-gray-600 text-sm mt-1">Gunakan fitur ini hanya jika mesin absensi rusak atau terjadi force majeure.</p>
        </div>
        <a href="{{ route('attendance.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
            &larr; Kembali ke Laporan
        </a>
    </div>
</div>

<!-- Step 1: Pilih Kelas dan Tanggal -->
<div class="bg-white border border-gray-200 rounded-lg shadow-sm p-4 mb-6">
    <form method="GET" action="{{ route('attendance.create') }}" class="flex flex-col sm:flex-row gap-4 items-end">
        <div class="w-full sm:w-1/3">
            <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
            <input type="date" name="date" id="date" value="{{ $date }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" required>
        </div>
        
        <div class="w-full sm:w-1/3">
            <label for="classroom_id" class="block text-sm font-medium text-gray-700 mb-1">Pilih Kelas</label>
            <select name="classroom_id" id="classroom_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" required>
                <option value="">-- Pilih Kelas --</option>
                @foreach($classrooms as $room)
                    <option value="{{ $room->id }}" {{ $classroomId == $room->id ? 'selected' : '' }}>
                        {{ $room->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="w-full sm:w-auto">
            <button type="submit" class="w-full bg-gray-800 hover:bg-gray-900 text-white font-medium py-2 px-6 rounded-md shadow-sm transition-colors text-sm">
                Tampilkan Siswa
            </button>
        </div>
    </form>
</div>

<!-- Step 2: Form Input Grid -->
@if($classroomId)
    @if($students->count() > 0)
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
        <form action="{{ route('attendance.store') }}" method="POST">
            @csrf
            <input type="hidden" name="date" value="{{ $date }}">
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/4">Siswa</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/4">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">Waktu Masuk</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">Waktu Pulang</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">Keterlambatan (Menit)</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($students as $index => $student)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="hidden" name="attendances[{{ $index }}][student_id]" value="{{ $student->id }}">
                                <div class="text-sm font-medium text-gray-900">{{ $student->name }}</div>
                                <div class="text-xs text-gray-500">NIS: {{ $student->nis }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <select name="attendances[{{ $index }}][status]" id="status_{{ $index }}" class="block w-full text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" onchange="toggleInputs({{ $index }})">
                                    <option value="alpha">Alpha (Tanpa Keterangan)</option>
                                    <option value="hadir">Hadir Tepat Waktu</option>
                                    <option value="terlambat">Hadir Terlambat</option>
                                    <option value="izin">Izin</option>
                                    <option value="sakit">Sakit</option>
                                </select>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="time" name="attendances[{{ $index }}][time_in]" id="time_in_{{ $index }}" class="block w-full text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:text-gray-400" disabled>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="time" name="attendances[{{ $index }}][time_out]" id="time_out_{{ $index }}" class="block w-full text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:text-gray-400" disabled>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="number" name="attendances[{{ $index }}][late_minutes]" id="late_minutes_{{ $index }}" min="0" value="0" class="block w-full text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:text-gray-400" disabled>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-md shadow-sm transition-colors">
                    Simpan Presensi Kelas
                </button>
            </div>
        </form>
    </div>

    <script>
        function toggleInputs(index) {
            const status = document.getElementById('status_' + index).value;
            const timeIn = document.getElementById('time_in_' + index);
            const timeOut = document.getElementById('time_out_' + index);
            const lateMins = document.getElementById('late_minutes_' + index);
            
            if (status === 'hadir' || status === 'terlambat') {
                timeIn.disabled = false;
                timeOut.disabled = false;
                if (status === 'terlambat') {
                    lateMins.disabled = false;
                } else {
                    lateMins.disabled = true;
                    lateMins.value = 0;
                }
            } else {
                timeIn.disabled = true;
                timeOut.disabled = true;
                lateMins.disabled = true;
                timeIn.value = '';
                timeOut.value = '';
                lateMins.value = 0;
            }
        }
    </script>
    @else
    <div class="text-center py-16 bg-white border border-gray-200 rounded-lg">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Belum ada siswa</h3>
        <p class="mt-1 text-sm text-gray-500">
            Tidak ada siswa yang aktif di kelas ini.
        </p>
    </div>
    @endif
@endif

@endsection
