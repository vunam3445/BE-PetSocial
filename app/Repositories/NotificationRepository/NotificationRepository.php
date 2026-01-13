<?php

namespace App\Repositories\NotificationRepository;

use App\Repositories\NotificationRepository\NotificationInterface;
use App\Models\User;
use Illuminate\Support\Facades\Log;
class NotificationRepository implements NotificationInterface{
    // App\Repositories\NotificationRepository\NotificationRepository.php

public function getNotificationsForUser(string $userId, int $limit = 15)
{
    $user = User::where('user_id', $userId)->first();
    
    if (!$user) {
        return [
            'unread_count' => 0,
            'pagination' => [
                'data' => [],
                'total' => 0
            ]
        ];
    }

    // 1. Lấy phân trang
    $notifications = $user->notifications()->paginate($limit);

    // 2. Đếm số chưa đọc
    $unreadCount = $user->unreadNotifications()->count();

    // 3. Ép kiểu về mảng và thêm unread_count
    // Việc này giữ nguyên toàn bộ key của paginate: data, current_page, last_page, total...
    $result = $notifications->toArray();
    $result['unread_count'] = $unreadCount;

    return $result;
}

    public function markAsRead(string $userId, string $notificationId)
    {
        $user = User::findOrFail($userId);
        return $user->unreadNotifications()
                    ->where('id', $notificationId)
                    ->update(['read_at' => now()]);
    }

    public function markAllAsRead(string $userId)
    {
        $user = User::findOrFail($userId);
        return $user->unreadNotifications()->update(['read_at' => now()]);
    }
}