<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class UserInteraction extends Model
{
    use HasUuids;

    protected $primaryKey = 'interaction_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['user_id', 'post_id', 'action_type', 'metadata','weight'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }
    protected $casts = [
        'metadata' => 'array', // Hoặc 'json'
    ];
}
