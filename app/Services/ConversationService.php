<?php

namespace App\Services;

use App\Repositories\ConversationRepository\ConversationRepository;
use App\Repositories\MessageRepository\MessageRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ConversationService
{
    protected $conversationRepo;
    protected $messageRepo;

    public function __construct(
        ConversationRepository $conversationRepo,
        MessageRepository $messageRepo
    ) {
        $this->conversationRepo = $conversationRepo;
        $this->messageRepo = $messageRepo;
    }

    public function getUserConversations(string $userId, ?string $filterStatus = null)
{
    $conversations = $this->conversationRepo->getUserConversations($userId);
    $result = [];

    foreach ($conversations as $conv) {
        $participants = $conv->participants()->get();
        $latestMessage = $this->messageRepo->getLatestMessage($conv->conversation_id);

        $status = 'unread';

        if ($latestMessage) {
            // lấy sender_id duy nhất trong cuộc trò chuyện
            $distinctSenders = $conv->messages()
                ->select('sender_id')
                ->distinct()
                ->pluck('sender_id');

            if ($distinctSenders->count() >= 2) {
                $status = 'read';
            }
        }

        if ($filterStatus === null || $status === $filterStatus) {
            $result[] = [
                'conversation'   => $conv,
                'latest_message' => $latestMessage,
                'status'         => $status,
            ];
        }
    }


    return $result;
}





    public function markAsRead(string $conversationId, string $userId)
    {
        return $this->conversationRepo->updateParticipant($conversationId, $userId, [
            'last_read_at' => Carbon::now()
        ]);
    }
    public function createConversation(array $data)
    {
        $userId = Auth::id();

        if (empty($data['participant_ids'])) {
            throw new \InvalidArgumentException("Participant IDs required");
        }

        // Trường hợp private chat: check trước khi tạo
        if (empty($data['is_group']) || !$data['is_group']) {
            $otherUserId = $data['participant_ids'][0]; // chỉ 1 user
            $existing = $this->conversationRepo->findPrivateConversation($userId, $otherUserId);

            if ($existing) {
                return $existing->load('participants:user_id,name,avatar_url')
                    ->makeHidden('participants.*.pivot');
            }
        }

        // Nếu chưa tồn tại thì tạo mới
        $conversation = $this->conversationRepo->create([
            'name' => $data['name'] ?? null,
            'is_group' => $data['is_group'] ?? false,
        ]);

        if ($conversation->is_group) {
            $conversation->participants()->attach($userId, ['role' => 'admin']);
            $conversation->participants()->attach($data['participant_ids'], ['role' => 'member']);
        } else {
            $conversation->participants()->attach([$userId, $data['participant_ids'][0]], ['role' => 'admin']);
        }

        return $conversation->load('participants:user_id,name,avatar_url')
            ->makeHidden('participants.*.pivot');
    }


    public function deleteConversation(string $conversationId)
    {
        return $this->conversationRepo->delete($conversationId);
    }

    public function getRecentConversations(int $limit = 10)
    {
        $userId = Auth::id();
        return $this->conversationRepo->RecentConversations($userId, $limit);
    }
}
