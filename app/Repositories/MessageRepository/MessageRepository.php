<?php

namespace App\Repositories\MessageRepository;

use App\Repositories\Base\BaseRepository;
use App\Models\Message;

class MessageRepository extends BaseRepository implements MessageInterface
{
    public function __construct(Message $model)
    {
        parent::__construct($model);
    }

    public function getLatestMessage(string $conversationId)
    {
        return $this->model
            ->where('conversation_id', $conversationId)
            ->latest()
            ->first();
    }

    public function getMessagesByConversation(string $conversationId, int $limit = 20)
    {
        return $this->model
            ->where('conversation_id', $conversationId)
            ->with('sender:user_id,name,avatar_url')
            ->orderBy('created_at', 'desc')
            ->paginate($limit);
    }
    public function getDistinctSenders(string $conversationId)
{
    return $this->model
        ->where('conversation_id', $conversationId)
        ->distinct()
        ->pluck('sender_id')
        ->toArray();
}

}
