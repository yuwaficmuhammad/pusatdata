@extends('layouts.app')

@section('title', 'Laporan Presensi')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Laporan Presensi Harian</h2>
        <p class="text-gray-600 text-sm mt-1">Pantau kehadiran siswa berdasarkan tanggal dan kelas secara *real-time*.</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('attendance.export') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
            <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Unduh Excel
        </a>
        <a href="{{ route('attendance.create', ['date' => $date, 'classroom_id' => $classroomId]) }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            + Input Presensi
        </a>
    </div>
</div>

<!-- Filter Section -->
<div class="bg-white border border-gray-200 rounded-lg shadow-sm p-4 mb-6">
    <form method="GET" action="{{ route('attendance.index') }}" class="flex flex-col sm:flex-row gap-4 items-end">
        <div class="w-full sm:w-1/3">
            <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
            <input type="date" name="date" id="date" value="{{ $date }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
        </div>
        
        <div class="w-full sm:w-1/3">
            <label for="classroom_id" class="block text-sm font-medium text-gray-700 mb-1">Kelas (Opsional)</label>
            <select name="classroom_id" id="classroom_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                <option value="">-- Semua Kelas --</option>
                @foreach($classrooms as $room)
                    <option value="{{ $room->id }}" {{ $classroomId == $room->id ? 'selected' : '' }}>
                        {{ $room->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="w-full sm:w-auto">
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-md shadow-sm transition-colors text-sm">
                Tampilkan Filter
            </button>
        </div>
    </form>
</div>

<!-- Table Section -->
<div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
    @if($attendances->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Profil Siswa</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kelas</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Masuk</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pulang</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">Aksi</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($attendances as $attn)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <img class="h-10 w-10 rounded-full border border-gray-200" src="https://ui-avatars.com/api/?name={{ urlencode($attn->student->name) }}&background=random" alt="{{ $attn->student->name }}">
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $attn->student->name }}</div>
                                    <div class="text-xs text-gray-500">NIS: {{ $attn->student->nis }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            @php
                                $activeClass = $attn->student->classrooms->first();
                            @endphp
                            {{ $activeClass ? $activeClass->name : '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                            {{ $attn->time_in ? \Carbon\Carbon::parse($attn->time_in)->format('H:i') : '--:--' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                            {{ $attn->time_out ? \Carbon\Carbon::parse($attn->time_out)->format('H:i') : '--:--' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @if($attn->status === 'hadir')
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Hadir Tepat Waktu</span>
                            @elseif($attn->status === 'terlambat')
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-100 text-orange-800">
                                    Terlambat ({{ $attn->late_minutes }} menit)
                                </span>
                            @elseif($attn->status === 'alpha')
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Alpha (Tanpa Keterangan)</span>
                            @else
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">{{ ucfirst($attn->status) }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="{{ route('attendance.edit', $attn->id) }}" class="text-blue-600 hover:text-blue-900">Edit</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="text-center py-16">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada data presensi</h3>
            <p class="mt-1 text-sm text-gray-500">
                Belum ada siswa yang melakukan absensi pada tanggal ini.
            </p>
        </div>
    @endif
</div>
@endsection
