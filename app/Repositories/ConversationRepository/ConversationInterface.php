<?php

namespace App\Repositories\ConversationRepository;

use App\Repositories\Base\BaseInterface;

interface ConversationInterface extends BaseInterface
{
    public function findPrivateConversation(string $userId, string $otherUserId);
    public function markOthersUnread($conversationId, $senderId);
    public function markAllRead($conversationId);
    public function RecentConversations($userId, $limit = 10);
    public function deleteConversationForUser(string $conversationId, string $userId);
    public function getMemberOfConversation(string $conversationId);
    public function getMembersToInvite(string $conversationId, int $limit);
    public function addParticipants(string $conversationId, array $userIds);
    public function changeRole(string $conversationId, string $tagetId);
    public function getUnreadConversationsCount(string $userId);
}
