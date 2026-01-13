<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ConversationParticipant extends Model
{
    use HasUuids;

    protected $table = 'conversation_participants';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'conversation_id',
        'user_id',
        'role',
        'last_read_at',
        'last_reply_at',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
