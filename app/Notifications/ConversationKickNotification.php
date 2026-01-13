<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Jobs\SendSocketNotificationJob;

class ConversationKickNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $conversation;
    protected $admin;

    /**
     * Khởi tạo thông báo với thông tin cuộc trò chuyện và admin thực hiện xóa
     */
    public function __construct($conversation, $admin)
    {
        $this->conversation = $conversation;
        $this->admin = $admin;
    }

    /**
     * Lưu thông báo vào bảng notifications trong database
     */
    public function via($notifiable): array
    {
        return ['database'];
    }

    /**
     * Format dữ liệu trả về để lưu vào cột 'data' và gửi Socket
     */
    public function toArray($notifiable): array
    {
        // 1. Format dữ liệu theo cấu trúc GroupKicked interface ở Frontend
        $notificationData = [
            'conversation_id'   => $this->conversation->conversation_id,
            'conversation_name' => $this->conversation->name,
            'avatar_url'        => $this->conversation->avatar_url, // Ảnh đại diện của cuộc trò chuyện
            'admin_name'        => $this->admin,
            'message'           => "Bạn đã bị quản trị viên {$this->admin} xóa khỏi nhóm {$this->conversation->name}.",
            'target_url'        => "", // Dẫn về trang tin nhắn chung
            'type'              => 'conversation_kicked',
            'created_at'        => now()->toDateTimeString(),
        ];

        // 2. Tạo object fullPayload giả lập cấu trúc Notification DB để gửi qua Socket
        $fullPayload = [
            'id'              => $this->id, 
            'type'            => get_class($this),
            'notifiable_id'   => $notifiable->user_id,
            'data'            => $notificationData, 
            'read_at'         => null,
            'created_at'      => now()->toDateTimeString(),
        ];

        // 3. Gửi Real-time sang Node.js qua Job để cập nhật UI ngay lập tức
        SendSocketNotificationJob::dispatch($notifiable->user_id, $fullPayload);

        return $notificationData;
    }
}