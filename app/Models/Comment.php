<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Comment extends Model
{
    use HasUuids;

    protected $primaryKey = 'comment_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['post_id', 'user_id', 'content', 'parent_id'];

    public function user() { return $this->belongsTo(User::class, 'user_id'); }
    public function post() { return $this->belongsTo(Post::class, 'post_id'); }
    public function parent() {
    return $this->belongsTo(Comment::class, 'parent_id', 'comment_id');
    }

    public function replies() {
    return $this->hasMany(Comment::class, 'parent_id', 'comment_id');
    }
}
