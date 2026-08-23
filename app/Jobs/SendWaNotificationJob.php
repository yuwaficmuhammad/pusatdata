<?php

namespace App\Jobs;

use App\Services\Interfaces\NotificationInterface;
use App\Services\WaNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendWaNotificationJob implements ShouldQueue
{
    use Queueable;

    /**
     * Berapa kali job boleh dicoba kembali (retry) jika gagal.
     */
    public $tries = 3;

    public function __construct(
        private string $targetPhone,
        private string $message
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Menyuntikkan concrete class secara manual atau menggunakan app()->make()
        // karena job serialized properties.
        $notificationService = app()->make(WaNotificationService::class);
        
        $success = $notificationService->send($this->targetPhone, $this->message);
        
        if (!$success) {
            Log::warning("Gagal mengirim WA notifikasi ke {$this->targetPhone}.");
            $this->release(60); // Coba lagi setelah 60 detik
        }
    }
}
