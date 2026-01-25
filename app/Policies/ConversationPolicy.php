<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Conversation;
use Illuminate\Support\Facades\DB;

class ConversationPolicy
{
    /**
     * Kiểm tra xem user có quyền xóa cuộc trò chuyện hay không.
     * (Phải là Admin trong bảng conversation_participants)
     */
    public function delete(User $user, Conversation $conversation)
    {
        // Kiểm tra trong bảng pivot
        $participant = DB::table('conversation_participants')
            ->where('conversation_id', $conversation->conversation_id)
            ->where('user_id', $user->user_id) // Giả định trường ID của bạn là user_id
            ->first();

        // Trả về true nếu tồn tại bản ghi và role là admin
        return $participant && $participant->role === 'admin';
    }
}