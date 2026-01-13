<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendSocketNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $receiverId;
    protected $notificationData;

    // Số lần thử lại nếu thất bại (ví dụ Node.js sập)
    public $tries = 3;

    public function __construct($receiverId, $notificationData)
    {
        $this->receiverId = $receiverId;
        $this->notificationData = $notificationData;
    }

    public function handle()
    {
        try {
            $response = Http::post("http://localhost:3001/broadcast-notification", [
                "receiver_id" => $this->receiverId,
                "notification" => $this->notificationData
            ]);

            if ($response->failed()) {
                throw new \Exception("Node.js server returned error");
            }
        } catch (\Exception $e) {
            Log::error("Job gửi thông báo lỗi: " . $e->getMessage());
            // Tự động đẩy lại vào hàng đợi để thử lại sau
            $this->release(30); 
        }
    }
}