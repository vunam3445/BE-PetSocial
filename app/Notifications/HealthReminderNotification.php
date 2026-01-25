<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Jobs\SendSocketNotificationJob;
use Carbon\Carbon;

class HealthReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $pet;
    protected $category;
    protected $dueDate;

    /**
     * @param $pet Object thông tin thú cưng
     * @param $category Object category (chứa name, category_id, interval_days)
     * @param $dueDate Đối tượng Carbon ngày đến hạn
     */
    public function __construct($pet, $category, $dueDate)
    {
        $this->pet = $pet;
        $this->category = $category;
        $this->dueDate = $dueDate;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        // 1. Format dữ liệu nòng cốt cho React render UI
        $notificationData = [
            'pet_id'            => $this->pet->pet_id,
            'pet_name'          => $this->pet->name,
            'avatar_url'        => $this->pet->avatar_url, // Ảnh của thú cưng để làm avatar thông báo
            'category_id'       => $this->category->category_id,
            'category_name'     => $this->category->name,
            'due_date'          => $this->dueDate->toDateString(),
            'message'           => "Bé {$this->pet->name} sắp đến lịch {$this->category->name} (Dự kiến: " . $this->dueDate->format('d/m/Y') . ").",
            'type'              => 'health_reminder', // Phân biệt với 'conversation_invitation'
            'created_at'        => now()->toDateTimeString(),
            'status'            => 'unread'
        ];

        // 2. Tạo object fullPayload khớp với cấu trúc bảng notifications và Socket server
        $fullPayload = [
            'id'              => $this->id, 
            'type'            => get_class($this),
            'notifiable_id'   => $notifiable->user_id, // Giả định user có PK là user_id
            'data'            => $notificationData, 
            'read_at'         => null,
            'created_at'      => now()->toDateTimeString(),
        ];

        // 3. Gửi Real-time qua Socket (Node.js) giống như lời mời vào nhóm
        SendSocketNotificationJob::dispatch($notifiable->user_id, $fullPayload);

        return $notificationData;
    }
}