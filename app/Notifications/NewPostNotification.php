<?php

namespace App\Notifications;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendSocketNotificationJob;

class NewPostNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $post;
    protected $author;

    public function __construct(Post $post)
    {
        $this->post = $post;
        $this->author = $post->author;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
{
    // Dữ liệu nội dung thông báo
    $notificationData = [
        'post_id'     => $this->post->post_id,
        'author_name' => $this->author->name,
        'avatar_url'  => $this->author->avatar_url,
        'message'     => "{$this->author->name} vừa đăng một bài viết mới.",
        'target_url'  => "/profile/{$this->post->author_id}",
        'type'        => 'new_post',
    ];

    /** * Tạo một object giả lập cấu trúc Notification trong DB 
     * để gửi qua Socket giúp React không bị lỗi undefined
     */
    $fullPayload = [
        'id'              => $this->id, // UUID tự động của Notification
        'type'            => get_class($this),
        'notifiable_id'   => $notifiable->user_id,
        'data'            => $notificationData, // Chứa nội dung chính
        'read_at'         => null,
        'created_at'      => now()->toDateTimeString(),
    ];

    // Gửi toàn bộ payload giả lập này sang Node.js
    SendSocketNotificationJob::dispatch($notifiable->user_id, $fullPayload);

    return $notificationData;
}
}
