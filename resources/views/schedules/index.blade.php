@extends('layouts.app')

@section('title', 'Jadwal Pelajaran')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Manajemen Jadwal Pelajaran</h2>
        <p class="text-gray-600 text-sm mt-1">Mengelola versi jadwal dan jam pelajaran untuk setiap kelas.</p>
    </div>
    <div>
        <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow-sm text-sm font-medium transition-colors">
            + Tambah Versi Jadwal
        </button>
    </div>
</div>

<div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6 mb-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-800">Versi Aktif: 
                @if($activeVersion)
                    <span class="text-green-600">{{ $activeVersion->name }}</span>
                @else
                    <span class="text-red-600">Tidak ada versi jadwal yang aktif</span>
                @endif
            </h3>
            @if($activeVersion)
                <p class="text-sm text-gray-500 mt-1">
                    Berlaku: {{ $activeVersion->valid_from->format('d M Y') }} s/d {{ $activeVersion->valid_until->format('d M Y') }} 
                    ({{ $activeVersion->semester->name }} - {{ $activeVersion->semester->academicYear->name }})
                </p>
            @endif
        </div>
        
        @if(count($versions) > 0)
        <div class="mt-4 sm:mt-0">
            <select class="border-gray-300 rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 text-sm">
                @foreach($versions as $version)
                    <option value="{{ $version->id }}" {{ $activeVersion && $activeVersion->id === $version->id ? 'selected' : '' }}>
                        {{ $version->name }} {{ $version->is_active ? '(Aktif)' : '' }}
                    </option>
                @endforeach
            </select>
        </div>
        @endif
    </div>

    <hr class="mb-4">

    @if($activeVersion && $activeVersion->activeDays->count() > 0)
        <!-- Menampilkan Jadwal per Hari -->
        <div class="space-y-6">
            @foreach($activeVersion->activeDays->sortBy('day_of_week') as $activeDay)
                <div class="border rounded-lg overflow-hidden">
                    <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex justify-between items-center">
                        <h4 class="font-bold text-gray-700">
                            Hari Ke-{{ $activeDay->day_of_week }} 
                            @if($activeDay->is_holiday) <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded ml-2">Libur</span> @endif
                        </h4>
                        <button class="text-sm text-blue-600 hover:text-blue-800 font-medium">Edit Hari Ini</button>
                    </div>
                    
                    @if(!$activeDay->is_holiday && $activeDay->schedules->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-white">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jam (Waktu)</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kelas</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mata Pelajaran</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Guru</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($activeDay->schedules->sortBy(fn($s) => $s->timeSlot->start_time) as $schedule)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $schedule->timeSlot->name }} <br>
                                            <span class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($schedule->timeSlot->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($schedule->timeSlot->end_time)->format('H:i') }}</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $schedule->classroom->name ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            @if($schedule->timeSlot->is_break)
                                                <span class="bg-orange-100 text-orange-800 text-xs px-2 py-1 rounded">ISTIRAHAT</span>
                                            @else
                                                <span class="font-medium text-gray-700">{{ $schedule->subject->name ?? '-' }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $schedule->teacher->name ?? '-' }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @elseif(!$activeDay->is_holiday)
                        <div class="p-6 text-center text-gray-500">
                            Belum ada jadwal yang ditambahkan untuk hari ini.
                        </div>
                    @else
                        <div class="p-6 text-center text-gray-500">
                            Hari ini ditetapkan sebagai hari libur pada versi jadwal ini.
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak Ada Jadwal</h3>
            <p class="mt-1 text-sm text-gray-500">
                Silakan buat versi jadwal dan tambahkan jadwal pelajaran.
            </p>
        </div>
    @endif
</div>
@endsection
