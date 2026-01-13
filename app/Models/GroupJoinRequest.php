<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupJoinRequest extends Model
{
    protected $table = 'group_join_requests';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $fillable = [
        'group_id',
        'user_id',
        'status'
    ];

    /** Request thuộc group nào */
    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id', 'group_id');
    }

    /** Request do user nào gửi */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /** 🔥 Request có nhiều câu trả lời */
    public function answers()
    {
        return $this->hasMany(GroupJoinAnswer::class, 'request_id', 'id');
    }
}
