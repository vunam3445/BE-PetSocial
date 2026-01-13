<?php
namespace App\Services;

use App\Repositories\NotificationRepository\NotificationInterface;
use Illuminate\Support\Facades\Auth;
class NotificationService
{
    protected $notificationRepo;

    public function __construct(NotificationInterface $notificationRepo)
    {
        $this->notificationRepo = $notificationRepo;
    }

    public function getMyNotifications(string $userId)
    {
        // Bạn có thể xử lý thêm logic ở đây (ví dụ: filter, format lại dữ liệu)
        return $this->notificationRepo->getNotificationsForUser($userId);
    }

    public function readNotification( string $notificationId)
    {
        $userId = Auth::id();
        return $this->notificationRepo->markAsRead($userId, $notificationId);
    }
}