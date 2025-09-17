<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class Follow extends Pivot
{
    protected $table = 'follows';
    public $incrementing = false;
    public $timestamps = true; // vì migration có timestamps

    protected $fillable = ['follower_id', 'followed_id'];

    public function follower()
    {
        return $this->belongsTo(User::class, 'follower_id', 'user_id');
    }

    public function followed()
    {
        return $this->belongsTo(User::class, 'followed_id', 'user_id');
    }
}
