<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Jobs\SendSocketNotificationJob;

class GroupJoinedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $group;

    public function __construct($group)
    {
        $this->group = $group;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        // 1. Format dữ liệu nội dung giống như Post (để React dùng chung logic render)
        $notificationData = [
            'group_id'    => $this->group->group_id,
            'group_name' => $this->group->name, // Dùng tên Group thay cho author_name
            'avatar_url'  => $this->group->avatar_url, // Ảnh đại diện của Group
            'message'     => "Bạn đã được chấp nhận vào nhóm {$this->group->name}.",
            'target_url'  => "/groups/{$this->group->group_id}",
            'type'        => 'group_accepted',
            'created_at'  => now()->toDateTimeString(),
        ];

        // 2. Tạo object fullPayload giả lập cấu trúc Notification DB
        $fullPayload = [
            'id'              => $this->id, // UUID của Notification
            'type'            => get_class($this),
            'notifiable_id'   => $notifiable->user_id,
            'data'            => $notificationData, // Chứa nội dung chính
            'read_at'         => null,
            'created_at'      => now()->toDateTimeString(),
        ];

        // 3. Gửi sang Node.js qua Job
        SendSocketNotificationJob::dispatch($notifiable->user_id, $fullPayload);

        return $notificationData;
    }
}