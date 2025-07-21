<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Message extends Model
{
    use HasUuids;

    protected $primaryKey = 'message_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'conversation_id', 'sender_id', 'content', 'media_url',
        'message_type', 'reply_to_id', 'deleted_at'
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function replyTo()
    {
        return $this->belongsTo(Message::class, 'reply_to_id');
    }
}
