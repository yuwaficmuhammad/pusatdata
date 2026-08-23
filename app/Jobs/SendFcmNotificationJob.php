<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SendFcmNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $fcmToken;
    public $title;
    public $body;

    /**
     * Create a new job instance.
     */
    public function __construct($fcmToken, $title, $body)
    {
        $this->fcmToken = $fcmToken;
        $this->title = $title;
        $this->body = $body;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (empty($this->fcmToken)) {
            return;
        }

        // Catatan: Ini adalah implementasi FCM Legacy API.
        // Untuk HTTP v1 API terbaru, gunakan package seperti kreait/laravel-firebase
        // atau generate Bearer token dari file service-account.json.
        // Di sini kita gunakan mock/placeholder untuk demonstrasi jika Server Key tidak ada.
        
        $serverKey = env('FCM_SERVER_KEY');
        
        if (empty($serverKey)) {
            Log::info("FCM Notification simulated for token {$this->fcmToken}: {$this->title}");
            return;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $serverKey,
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'to' => $this->fcmToken,
                'notification' => [
                    'title' => $this->title,
                    'body' => $this->body,
                    'sound' => 'default'
                ],
                'data' => [
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'type' => 'attendance'
                ]
            ]);

            if (!$response->successful()) {
                Log::error("FCM Error: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("FCM Exception: " . $e->getMessage());
        }
    }
}
