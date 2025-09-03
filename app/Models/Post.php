<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasUuids;

    protected $primaryKey = 'post_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'author_id',
        'caption',
        'media_url',
        'media_type',
        'visibility',
        'shared_post_id',
        'group_id'
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id');
    }
    public function sharedPost()
    {
        return $this->belongsTo(Post::class, 'shared_post_id');
    }
    public function comments()
    {
        return $this->hasMany(Comment::class, 'post_id');
    }
    public function likes()
    {
        return $this->hasMany(Like::class, 'post_id');
    }
    
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'post_tags', 'post_id', 'tag_id')
        ->withTimestamps();
    }
    public function interactions()
    {
        return $this->hasMany(UserInteraction::class, 'post_id');
    }
    public function media()
    {
        return $this->hasMany(PostMedia::class, 'post_id');
    }


        
protected static function boot()
{
    parent::boot();

    static::creating(function ($model) {
        if (empty($model->post_id)) {
            $model->post_id = (string) Str::uuid();
        }
    });
}
}
