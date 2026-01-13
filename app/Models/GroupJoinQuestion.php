<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class GroupJoinQuestion extends Model
{
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $table = 'group_join_questions';

    protected $fillable = [
        'group_id',
        'question'
    ];

    /** Câu hỏi thuộc group nào */
    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id', 'group_id');
    }

    /** 🔥 Một câu hỏi có nhiều câu trả lời */
    public function answers()
    {
        return $this->hasMany(GroupJoinAnswer::class, 'question_id', 'id');
    }
}
