<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Jobs\SendSocketNotificationJob;
use Illuminate\Support\Facades\Log;
class GroupJoinRequestNotification extends Notification
{
    use Queueable;

    protected $request;

    public function __construct($request)
    {
        // Eager load các quan hệ để tránh N+1 và mất dữ liệu khi gửi sang Queue
        $this->request = $request;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $user = $this->request->user;
        $group = $this->request->group;

        // Sử dụng tên quan hệ đúng: answers (thay vì joinAnswers)
        $questionsAndAnswers = $this->request->answers->map(function ($item) {
            return [
                'question_id' => $item->question_id,
                'question'    => $item->question->question ?? 'N/A',
                'answer'      => $item->answer
            ];
        });
        $notificationData = [
            'type'       => 'GROUP_JOIN_REQUEST', // FE sẽ bắt type này để hiển thị UI duyệt/hủy
            'message'    => "Người dùng {$user->name} đã gửi yêu cầu tham gia nhóm {$group->name}.",
            'group_id'   => $this->request->group_id,
            'group_name' => $group->name,
            'avatar_url' => $user->avatar_url, // Ảnh người gửi yêu cầu
            'created_at' => now()->toDateTimeString(),
            'status'     => 'pending',
            'target_url' => "/groups/{$this->request->group_id}",
            'data'       => [
                'request_id' => $this->request->id,
                'user_id'    => $user->user_id,
                'name'       => $user->name,
                'status'     => $this->request->status,
                'questions'  => $questionsAndAnswers
            ]
        ];

        // 2. Tạo object fullPayload khớp với cấu trúc bảng notifications và chuẩn chung của hệ thống
        $fullPayload = [
            'id'            => $this->id,
            'type'          => get_class($this),
            'notifiable_id' => $notifiable->user_id,
            'data'          => $notificationData,
            'read_at'       => null,
            'created_at'    => now()->toDateTimeString(),
        ];

        // 3. Gửi Real-time sang Socket Server thông qua Job
        // Chúng ta dùng SendSocketGroupJob vì gửi cho Admin nhóm
        SendSocketNotificationJob::dispatch($notifiable->user_id, $fullPayload);

        return $notificationData;
    }
}
