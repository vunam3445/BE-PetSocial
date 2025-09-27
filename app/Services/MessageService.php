<?php

namespace App\Services;

use App\Repositories\MessageRepository\MessageRepository;
use App\Repositories\ConversationRepository\ConversationRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class MessageService
{
    protected $messageRepo;
    protected $conversationRepo;

    public function __construct(MessageRepository $messageRepo, ConversationRepository $conversationRepo)
    {
        $this->messageRepo = $messageRepo;
        $this->conversationRepo = $conversationRepo;
    }

    public function sendMessage(
        string $userId,
        string $conversationId,
        string $content,
        ?string $media = null,
        ?string $replyToId = null
    ) {
        $message = $this->messageRepo->create([
            'conversation_id' => $conversationId,
            'sender_id'       => $userId,
            'content'         => $content,
            'media'           => $media,
            'message_type'    => $media ? 'media' : 'text',
            'reply_to_id'     => $replyToId,
        ]);
        $message->load(['sender:user_id,avatar_url']);
        // Chỉ update last_reply_at cho người gửi
        $this->conversationRepo->updateParticipant($conversationId, $userId, [
            'last_reply_at' => now()
        ]);
        try {
            Http::post("http://localhost:3001/broadcast", [
                "conversationId" => $conversationId,
                "message_id"     => $message->message_id,
                "sender_id"      => $userId,
                "content"        => $content,
                "media"          => $media,
                "created_at"     => $message->created_at,
                "sender"         => $message->sender,
            ]);
        } catch (Exception $e) {
            Log::error("Broadcast failed: " . $e->getMessage());
        }
        return $message;
    }





    public function getMessages(string $conversationId, int $limit = 15)
    {
        return $this->messageRepo->getMessagesByConversation($conversationId, $limit);
    }
}
