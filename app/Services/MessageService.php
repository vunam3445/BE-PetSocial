<?php

namespace App\Services;

use App\Repositories\MessageRepository\MessageRepository;
use App\Repositories\ConversationRepository\ConversationRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
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
    ?string $content,
    ?UploadedFile $media = null, // Đổi từ string sang UploadedFile
    ?string $replyToId = null
) {
    $mediaUrl = null;
    $messageType = 'text';

    // Xử lý upload ảnh/video
if ($media) {
    $path = $media->store('messages', 'public');
    $mediaUrl = url(Storage::url($path)); 
    
    $mimeType = $media->getMimeType(); // Lấy định dạng tệp (vd: image/jpeg, video/mp4)

    if (str_starts_with($mimeType, 'image/')) {
        $messageType = 'image';
    } elseif (str_starts_with($mimeType, 'video/')) {
        $messageType = 'video';
    } else {
        $messageType = 'media'; // Trường hợp là file khác (zip, pdf...)
    }
}
    $message = $this->messageRepo->create([
        'conversation_id' => $conversationId,
        'sender_id'       => $userId,
        'content'         => $content,
        'media_url'       => $mediaUrl, // Lưu URL vào cột media_url
        'message_type'    => $messageType,
        'reply_to_id'     => $replyToId,
    ]);

    $message->load(['sender:user_id,avatar_url,name']);

    $this->conversationRepo->updateParticipant($conversationId, $userId, [
        'last_reply_at' => now()
    ]);

    try {
        Http::post("http://localhost:3001/broadcast", [
            "conversationId" => $conversationId,
            "message_id"     => $message->message_id,
            "sender_id"      => $userId,
            "content"        => $content,
            "media_url"      => $mediaUrl,
            "message_type"   => $messageType,
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
