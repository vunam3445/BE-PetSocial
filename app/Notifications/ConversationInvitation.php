<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Jobs\SendSocketNotificationJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
class ConversationInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    protected $conversation;
    protected $userData;
    public function __construct($conversation, array $userData)
    {
        $this->conversation = $conversation;
        $this->userData = $userData;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        // 1. Format dữ liệu nòng cốt để React render UI và xử lý Action
        $notificationData = [
            'conversation_id'   => $this->conversation->conversation_id,
            'conversation_name' => $this->conversation->name,
            'avatar_url'        => $this->conversation->avatar_url, 
            'inviter_id'        => $this->userData['user_id'],
            'inviter_name'      => $this->userData['name'],
            'message'           => "đã mời bạn tham gia nhóm {$this->conversation->name}.",
            'type'              => 'conversation_invitation', // Quan trọng: Để React bắt case này hiển thị 2 nút
            'created_at'        => now()->toDateTimeString(),
            'status'            => 'pending'
        ];

        // 2. Tạo object fullPayload khớp với cấu trúc bảng notifications
        $fullPayload = [
            'id'              => $this->id, 
            'type'            => get_class($this),
            'notifiable_id'   => $notifiable->user_id,
            'data'            => $notificationData, 
            'read_at'         => null,
            'created_at'      => now()->toDateTimeString(),
        ];

        // 3. Gửi Real-time sang Socket Server (Node.js)
        SendSocketNotificationJob::dispatch($notifiable->user_id, $fullPayload);

        return $notificationData;
    }
}