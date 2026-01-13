<?php
namespace App\Repositories\NotificationRepository;

interface NotificationInterface {
    public function getNotificationsForUser(string $userId, int $limit = 15);
    public function markAsRead(string $userId, string $notificationId);
    public function markAllAsRead(string $userId);
}