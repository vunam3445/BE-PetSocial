<?php 
namespace App\Models;

use Illuminate\Notifications\DatabaseNotification;

class Notification extends DatabaseNotification
{
    
    protected $keyType = 'string';
    public $incrementing = false;

    public function getSenderAvatarAttribute()
    {
        return $this->data['avatar_url'] ?? '/default-avatar.png';
    }
}