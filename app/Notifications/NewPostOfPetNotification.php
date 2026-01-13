<?php

namespace App\Notifications;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Jobs\SendSocketNotificationJob;

class NewPostOfPetNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $post;
    protected $pet;

    public function __construct(Post $post)
    {
        // Load quan hệ pet để tránh N+1 query khi gửi cho nhiều người
        $this->post = $post->load('pet');
        $this->pet = $post->pet;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        // Dữ liệu nội dung thông báo dựa trên thú cưng
        $notificationData = [
            'post_id'     => $this->post->post_id,
            'pet_id'      => $this->pet->pet_id,
            'author_name' => $this->pet->name, // Tên của thú cưng
            'avatar_url'  => $this->pet->avatar_url, // Avatar của thú cưng
            'message'     => "Thú cưng {$this->pet->name} vừa có một bài viết mới.",
            'target_url'  => "/pet-health/{$this->pet->pet_id}", // Đường dẫn tới profile pet
            'type'        => 'new_post_of_pet',
        ];

        // Tạo payload giả lập cho Socket
        $fullPayload = [
            'id'              => $this->id,
            'type'            => get_class($this),
            'notifiable_id'   => $notifiable->user_id,
            'data'            => $notificationData,
            'read_at'         => null,
            'created_at'      => now()->toDateTimeString(),
        ];

        // Gửi qua Node.js để hiển thị Real-time
        SendSocketNotificationJob::dispatch($notifiable->user_id, $fullPayload);

        return $notificationData;
    }
}