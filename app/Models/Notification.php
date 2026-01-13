<?php 
namespace App\Models;

use Illuminate\Notifications\DatabaseNotification;

class Notification extends DatabaseNotification
{
    // Quan trọng: Bảng notifications mặc định dùng UUID làm ID trong migration của bạn
    protected $keyType = 'string';
    public $incrementing = false;

    public function getSenderAvatarAttribute()
    {
        return $this->data['avatar_url'] ?? '/default-avatar.png';
    }
}