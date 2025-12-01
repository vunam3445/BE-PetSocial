<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;


class Group extends Model
{
    use HasUuids;

    protected $primaryKey = 'group_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['name', 'description', 'avatar_url', 'cover_url', 'visibility', 'created_by', 'require_post_approval'];

    public function creator()      { return $this->belongsTo(User::class, 'created_by'); }
    public function posts()        { return $this->hasMany(Post::class, 'group_id'); }
    public function members()      { return $this->belongsToMany(User::class, 'group_members', 'group_id', 'user_id')->withPivot('role', 'joined_at'); }
    public function joinQuestions() {
    return $this->hasMany(GroupJoinQuestion::class, 'group_id', 'group_id');
}
}
