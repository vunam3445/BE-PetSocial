<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Jobs\SendSocketNotificationJob;

class PostDeletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $group;
    protected $reason;

    /**
     * @param $group: Để lấy tên nhóm và avatar nhóm làm "người gửi"
     * @param string $reason: Lý do xóa (nếu bạn muốn hiển thị chi tiết)
     */
    public function __construct($group, $reason = "vi phạm quy chuẩn cộng đồng")
    {
        $this->group = $group;
        $this->reason = $reason;
    }

    public function via(object $notifiable): array
    {
        // Trả về 'database' để lưu vào bảng notifications
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        // 1. Dữ liệu hiển thị trên FE (Format giống các thông báo khác)
        $notificationData = [
            'author_name' => $this->group->name ?? 'Hệ thống',
            'avatar_url'  => $this->group->avatar_url ?? null,
            'message'     => "Bài viết của bạn đã bị xóa do {$this->reason}.",
            'target_url'  => $this->group ? "/groups/{$this->group->group_id}" : "/",
            'type'        => 'post_deleted_violation',
            'created_at'  => now()->toDateTimeString(),
        ];

        // 2. Payload gửi qua Socket (Full cấu trúc để React không bị lỗi)
        $fullPayload = [
            'id'              => $this->id,
            'type'            => get_class($this),
            'notifiable_id'   => $notifiable->user_id,
            'data'            => $notificationData,
            'read_at'         => null,
            'created_at'      => now()->toDateTimeString(),
        ];

        // 3. Dispatch Job gửi Real-time sang Node.js
        SendSocketNotificationJob::dispatch($notifiable->user_id, $fullPayload);

        return $notificationData;
    }
}