<?php

namespace App\Services;

use App\Services\Interfaces\NotificationInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WaNotificationService implements NotificationInterface
{
    public function send(string $target, string $message): bool
    {
        $apiUrl = config('services.fonnte.url', 'https://api.fonnte.com/send');
        $token = config('services.fonnte.token');

        if (empty($token)) {
            Log::warning('Fonnte token belum diset di .env');
            return false;
        }

        // Format nomor target: jika diawali 0, ganti ke 62
        $target = $this->formatNumber($target);
        if (!$target) {
            Log::warning("Format nomor target tidak valid: {$target}");
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
            ])->post($apiUrl, [
                'target' => $target,
                'message' => $message,
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::error("Fonnte API Error: " . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("Gagal koneksi ke Fonnte: " . $e->getMessage());
            return false;
        }
    }

    private function formatNumber(string $number): ?string
    {
        $number = preg_replace('/[^0-9]/', '', $number); // Hanya angka

        if (str_starts_with($number, '08')) {
            return '62' . substr($number, 1);
        }

        if (str_starts_with($number, '628')) {
            return $number;
        }

        // Jika nomor tidak diawali 08 atau 628, asumsikan tidak valid
        return null;
    }
}
