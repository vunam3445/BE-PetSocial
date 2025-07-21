<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Conversation extends Model
{
    use HasUuids;

    protected $primaryKey = 'conversation_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['name', 'is_group'];

    public function participants()
    {
        return $this->belongsToMany(User::class, 'conversation_participants', 'conversation_id', 'user_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'conversation_id');
    }
}
