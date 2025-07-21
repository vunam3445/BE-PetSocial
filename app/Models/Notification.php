<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Notification extends Model
{
    use HasUuids;

    protected $primaryKey = 'notification_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['sender_id', 'type', 'content', 'target_url'];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receivers()
    {
        return $this->belongsToMany(User::class, 'user_notifications', 'notification_id', 'user_id');
    }
}
