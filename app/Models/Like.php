<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class Like extends Pivot
{
    protected $table = 'likes';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['user_id', 'post_id', 'liked_at'];
    public function delete()
    {
        return static::where('user_id', $this->user_id)
            ->where('post_id', $this->post_id)
            ->delete();
    }
}
