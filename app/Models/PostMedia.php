<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PostMedia extends Model
{
    use HasUuids;

    protected $primaryKey = 'media_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'post_id',
        'media_url',
        'media_type',
        'order'
    ];

    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }
}
