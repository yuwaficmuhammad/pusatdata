@extends('layouts.app')

@section('title', 'Edit Presensi')

@section('content')
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Edit Presensi Manual</h2>
            <p class="text-gray-600 text-sm mt-1">Ubah status atau jam presensi siswa: {{ $attendance->student->name }}</p>
        </div>
        <a href="{{ route('attendance.index', ['date' => $attendance->date->format('Y-m-d')]) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
            &larr; Kembali ke Laporan
        </a>
    </div>
</div>

<div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6 max-w-2xl">
    <form action="{{ route('attendance.update', $attendance->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <!-- Info Siswa -->
        <div class="mb-6 bg-gray-50 p-4 rounded-md border border-gray-200 flex items-center">
            <div class="flex-shrink-0 h-12 w-12 mr-4">
                <img class="h-12 w-12 rounded-full border border-gray-300" src="https://ui-avatars.com/api/?name={{ urlencode($attendance->student->name) }}&background=random" alt="{{ $attendance->student->name }}">
            </div>
            <div>
                <h3 class="text-lg font-medium text-gray-900">{{ $attendance->student->name }}</h3>
                <p class="text-sm text-gray-500">NIS: {{ $attendance->student->nis }} | Tanggal: {{ $attendance->date->format('d M Y') }}</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-4">
                <div class="flex">
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Terdapat kesalahan input:</h3>
                        <ul class="mt-1 text-sm text-red-700 list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <div class="space-y-4">
            <!-- Status -->
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700">Status Presensi <span class="text-red-500">*</span></label>
                <select id="status" name="status" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md" required onchange="toggleTimeInputs()">
                    <option value="hadir" {{ old('status', $attendance->status) == 'hadir' ? 'selected' : '' }}>Hadir</option>
                    <option value="terlambat" {{ old('status', $attendance->status) == 'terlambat' ? 'selected' : '' }}>Terlambat</option>
                    <option value="izin" {{ old('status', $attendance->status) == 'izin' ? 'selected' : '' }}>Izin</option>
                    <option value="sakit" {{ old('status', $attendance->status) == 'sakit' ? 'selected' : '' }}>Sakit</option>
                    <option value="alpha" {{ old('status', $attendance->status) == 'alpha' ? 'selected' : '' }}>Alpha</option>
                </select>
            </div>

            <!-- Waktu Masuk & Pulang -->
            <div id="time_inputs" class="grid grid-cols-1 gap-y-4 gap-x-4 sm:grid-cols-2 pt-2">
                <div>
                    <label for="time_in" class="block text-sm font-medium text-gray-700">Waktu Masuk</label>
                    <div class="mt-1">
                        @php
                            $defaultTimeIn = $attendance->time_in ? \Carbon\Carbon::parse($attendance->time_in)->format('H:i') : '';
                        @endphp
                        <input type="time" name="time_in" id="time_in" value="{{ old('time_in', $defaultTimeIn) }}" class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md">
                    </div>
                </div>
                
                <div>
                    <label for="time_out" class="block text-sm font-medium text-gray-700">Waktu Pulang</label>
                    <div class="mt-1">
                        @php
                            $defaultTimeOut = $attendance->time_out ? \Carbon\Carbon::parse($attendance->time_out)->format('H:i') : '';
                        @endphp
                        <input type="time" name="time_out" id="time_out" value="{{ old('time_out', $defaultTimeOut) }}" class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md">
                    </div>
                </div>
            </div>

            <!-- Menit Keterlambatan -->
            <div id="late_minutes_input" class="pt-2">
                <label for="late_minutes" class="block text-sm font-medium text-gray-700">Menit Keterlambatan</label>
                <div class="mt-1 flex rounded-md shadow-sm">
                    <input type="number" name="late_minutes" id="late_minutes" min="0" value="{{ old('late_minutes', $attendance->late_minutes) }}" class="flex-1 focus:ring-blue-500 focus:border-blue-500 block w-full min-w-0 sm:text-sm border-gray-300 rounded-md">
                    <span class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-gray-300 bg-gray-50 text-gray-500 sm:text-sm ml-2">
                        Menit
                    </span>
                </div>
                <p class="mt-2 text-sm text-gray-500">Hanya diisi jika status = Terlambat.</p>
            </div>
        </div>

        <div class="mt-8 pt-5 border-t border-gray-200">
            <div class="flex justify-end">
                <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Simpan Perubahan
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    function toggleTimeInputs() {
        const status = document.getElementById('status').value;
        const timeInputs = document.getElementById('time_inputs');
        const lateMinutesInput = document.getElementById('late_minutes_input');
        
        if (status === 'hadir' || status === 'terlambat') {
            timeInputs.style.display = 'grid';
            if (status === 'terlambat') {
                lateMinutesInput.style.display = 'block';
            } else {
                lateMinutesInput.style.display = 'none';
            }
        } else {
            // Izin, Sakit, Alpha tidak butuh jam absen
            timeInputs.style.display = 'none';
            lateMinutesInput.style.display = 'none';
            // Clear values
            document.getElementById('time_in').value = '';
            document.getElementById('time_out').value = '';
            document.getElementById('late_minutes').value = '0';
        }
    }
    
    // Initial run
    document.addEventListener('DOMContentLoaded', toggleTimeInputs);
</script>
@endsection
