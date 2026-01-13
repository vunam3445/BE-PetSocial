<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class GroupMember extends Pivot
{
    protected $table = 'group_members';
    public $incrementing = false;
    public $timestamps = false;
    public $fillable = ['group_id', 'user_id', 'role', 'joined_at'];
}
