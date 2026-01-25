<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Jobs\SendSocketNotificationJob;
use Illuminate\Queue\SerializesModels;

class ConversationDeletedNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected $convData;
    protected $actorName;
    protected $currentDomain;

    public function __construct($conversation, $actorName, $currentDomain)
    {
        $this->actorName = $actorName;
        $this->convData = [
            'conversation_id'   => $conversation->conversation_id,
            'conversation_name' => $conversation->name, 
            'is_group'          => (bool)$conversation->is_group,
            'avatar_url'        => $conversation->avatar_url,
        ];
        $this->currentDomain = $currentDomain;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $isGroup = $this->convData['is_group'];
        $convName = $this->convData['conversation_name'];
        
        // --- THAY ĐỔI TẠI ĐÂY ---
        // 1. Xác định nội dung thông báo và Avatar dựa trên type
        if ($isGroup) {
            $message = "{$this->actorName} đã giải tán nhóm \"{$convName}\". Nhóm này không còn tồn tại.";
            $avatar = $this->convData['avatar_url']; // Giữ nguyên avatar nhóm
        } else {
            $message = "{$this->actorName} đã xóa cuộc trò chuyện với bạn.";
            // Sử dụng avatar hệ thống cho private chat
            $avatar = $this->currentDomain . '/storage/avatars/avatar_system.png';      
            }
        // -----------------------

        $notificationData = [
            'conversation_id'   => $this->convData['conversation_id'],
            'conversation_name' => $convName,
            'avatar_url'        => $avatar, // Sử dụng biến avatar vừa xử lý
            'message'           => $message,
            'type'              => 'conversation_deleted',
            'created_at'        => now()->toDateTimeString(),
        ];

        // Gửi Socket Realtime
        $fullPayload = [
            'id'              => $this->id, 
            'type'            => 'conversation_deleted',
            'notifiable_id'   => $notifiable->user_id,
            'data'            => $notificationData, 
            'read_at'         => null,
            'created_at'      => now()->toDateTimeString(),
        ];

        SendSocketNotificationJob::dispatch($notifiable->user_id, $fullPayload);

        return $notificationData;
    }
}