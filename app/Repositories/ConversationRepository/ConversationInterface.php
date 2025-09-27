<?php
namespace App\Repositories\ConversationRepository;
use App\Repositories\Base\BaseInterface;
interface ConversationInterface extends BaseInterface{
public function findPrivateConversation(string $userId, string $otherUserId);
public function markOthersUnread($conversationId, $senderId);
public function markAllRead($conversationId);
public function RecentConversations($userId, $limit = 10);

} 