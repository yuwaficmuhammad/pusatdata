@extends('layouts.app')

@section('title', 'Dashboard Statistik')

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Dashboard Statistik Kehadiran</h2>
    <p class="text-gray-600 text-sm mt-1">
        @if(auth()->user()->role === 'homeroom_teacher')
            Menampilkan data khusus kelas yang Anda ampu.
        @else
            Menampilkan data seluruh kelas (Mode Admin).
        @endif
    </p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Pie Chart Card -->
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Ringkasan Hari Ini ({{ \Carbon\Carbon::today()->format('d M Y') }})</h3>
        <div class="relative h-64">
            <canvas id="todayPieChart"></canvas>
        </div>
        <div class="mt-4 grid grid-cols-2 gap-4 text-sm text-center">
            <div class="bg-green-50 p-2 rounded">
                <div class="font-bold text-green-700">{{ $statsToday['hadir'] }}</div>
                <div class="text-green-600">Hadir</div>
            </div>
            <div class="bg-blue-50 p-2 rounded">
                <div class="font-bold text-blue-700">{{ $statsToday['izin'] }}</div>
                <div class="text-blue-600">Izin</div>
            </div>
            <div class="bg-yellow-50 p-2 rounded">
                <div class="font-bold text-yellow-700">{{ $statsToday['sakit'] }}</div>
                <div class="text-yellow-600">Sakit</div>
            </div>
            <div class="bg-red-50 p-2 rounded">
                <div class="font-bold text-red-700">{{ $statsToday['alpha'] }}</div>
                <div class="text-red-600">Alpha</div>
            </div>
        </div>
    </div>

    <!-- Bar Chart Card -->
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Tren Keterlambatan (7 Hari Terakhir)</h3>
        <p class="text-xs text-gray-500 mb-4">Total menit keterlambatan akumulatif per hari</p>
        <div class="relative h-64">
            <canvas id="lateBarChart"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Pie Chart
        const pieCtx = document.getElementById('todayPieChart').getContext('2d');
        new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels: ['Hadir', 'Izin', 'Sakit', 'Alpha', 'Belum Absen'],
                datasets: [{
                    data: [
                        {{ $statsToday['hadir'] }},
                        {{ $statsToday['izin'] }},
                        {{ $statsToday['sakit'] }},
                        {{ $statsToday['alpha'] }},
                        {{ $statsToday['belum_absen'] }}
                    ],
                    backgroundColor: [
                        '#10B981', // green-500
                        '#3B82F6', // blue-500
                        '#F59E0B', // yellow-500
                        '#EF4444', // red-500
                        '#E5E7EB'  // gray-200
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });

        // Bar Chart
        const barCtx = document.getElementById('lateBarChart').getContext('2d');
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($lateTrend['labels']) !!},
                datasets: [{
                    label: 'Total Keterlambatan (Menit)',
                    data: {!! json_encode($lateTrend['data']) !!},
                    backgroundColor: '#F97316', // orange-500
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    });
</script>
@endsection
