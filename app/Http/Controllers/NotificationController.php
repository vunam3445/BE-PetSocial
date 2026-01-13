<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected $notificationService;
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService= $notificationService;
    }
    public function index(string $userId)
    {
        $notifications = $this->notificationService->getMyNotifications($userId);
        return response()->json($notifications);
    }
    public function readNotification(string $notificationId)
    {
        return $this->notificationService->readNotification($notificationId);
    }
}
