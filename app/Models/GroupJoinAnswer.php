<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupJoinAnswer extends Model
{
    protected $table = 'group_join_answers';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $fillable = [
        'question_id',
        'request_id',
        'user_id',
        'answer'
    ];

    /** Câu trả lời thuộc câu hỏi nào */
    public function question()
    {
        return $this->belongsTo(GroupJoinQuestion::class, 'question_id', 'id');
    }

    /** 🔥 Câu trả lời thuộc request nào */
    public function request()
    {
        return $this->belongsTo(GroupJoinRequest::class, 'request_id', 'id');
    }

    /** Ai trả lời */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
