<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Jobs\SendSocketNotificationJob;

class GroupKickedNotification extends Notification implements ShouldQueue
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
        $notificationData = [
            'group_id'    => $this->group->group_id,
            'group_name' => $this->group->name,
            'avatar_url'  => $this->group->avatar_url,
            'message'     => "Bạn đã bị xóa khỏi nhóm {$this->group->name}.",
            'target_url'  => "/groups/{$this->group->group_id}", // Khi bị đuổi thì cho về trang danh sách nhóm chung
            'type'        => 'group_kicked',
            'created_at'  => now()->toDateTimeString(),
        ];

        $fullPayload = [
            'id'              => $this->id,
            'type'            => get_class($this),
            'notifiable_id'   => $notifiable->user_id,
            'data'            => $notificationData,
            'read_at'         => null,
            'created_at'      => now()->toDateTimeString(),
        ];

        SendSocketNotificationJob::dispatch($notifiable->user_id, $fullPayload);

        return $notificationData;
    }
}