<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Access\Response;

class MessagePolicy
{
    public function isMember(User $user, string $conversationId)
    {
        $isMember = DB::table('conversation_participants')
            ->where('conversation_id', $conversationId)
            ->where('user_id', $user->user_id)
            ->exists();

        return $isMember 
            ? Response::allow() 
            : Response::deny('Bạn không có quyền thực hiện hành động này vì không phải thành viên của cuộc hội thoại.');
    }
}