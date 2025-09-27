<?php

namespace App\Repositories\ConversationRepository;

use App\Repositories\Base\BaseRepository;
use App\Models\Conversation;
use Illuminate\Support\Facades\DB;

class ConversationRepository extends BaseRepository implements ConversationInterface
{
    public function __construct(Conversation $model)
    {
        parent::__construct($model);
    }

    public function getUserConversations(string $userId)
    {
        return $this->model->whereHas('participants', function ($q) use ($userId) {
            $q->where('conversation_participants.user_id', $userId);
        })
            ->with([
                'participants' => function ($q) use ($userId) {
                    $q->where('conversation_participants.user_id', '!=', $userId)
                        ->select('users.user_id', 'users.name', 'users.avatar_url');
                },

            ])
            ->get();
    }


    public function updateParticipant($conversationId, $userId, array $data)
    {
        return DB::table('conversation_participants')
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->update($data);
    }
    public function findPrivateConversation(string $userId, string $otherUserId)
    {
        return $this->model
            ->where('is_group', false)
            ->whereHas('participants', fn($q) => $q->where('conversation_participants.user_id', $userId))
            ->whereHas('participants', fn($q) => $q->where('conversation_participants.user_id', $otherUserId))
            ->first();
    }
    public function markOthersUnread($conversationId, $senderId)
    {
        return DB::table('conversation_participants')
            ->where('conversation_id', $conversationId)
            ->where('user_id', '!=', $senderId)
            ->update([
                'last_reply_at' => null // hoặc giữ nguyên, tuỳ bạn muốn flag unread bằng cách nào
            ]);
    }
    public function markAllRead($conversationId)
    {
        return DB::table('conversation_participants')
            ->where('conversation_id', $conversationId)
            ->update([
                'last_reply_at' => now()
            ]);
    }
    public function RecentConversations($userId, $limit = 10)
    {
        return $this->model
            ->where('is_group', false) // chỉ lấy 1-1
            ->whereHas('participants', function ($q) use ($userId) {
                $q->where('conversation_participants.user_id', $userId);
            })
            ->with([
                'participants' => function ($q) use ($userId) {
                    $q->where('conversation_participants.user_id', '!=', $userId)
                        ->select('users.user_id', 'users.name', 'users.avatar_url');
                }
            ])
            ->latest() // sắp xếp theo thời gian tạo conversation
            ->limit($limit)
            ->get();
    }
}
