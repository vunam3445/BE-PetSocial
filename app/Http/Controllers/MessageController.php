<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MessageService;
use Illuminate\Support\Facades\Auth;
use App\Models\Message;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class MessageController extends Controller
{   
    use AuthorizesRequests;
    protected $messageService;

    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }

    public function index(string $conversationId)
    {
        $this->authorize('isMember', [Message::class, $conversationId]);
        $messages = $this->messageService->getMessages($conversationId);

        return response()->json($messages);
    }

    public function store(Request $request, string $conversationId)
    {
        $this->authorize('isMember', [Message::class, $conversationId]);
        
        $request->validate([
            'content' => 'nullable|string',
            'media' => 'nullable|file|mimes:jpg,jpeg,png,gif,mp4,mov,avi|max:20480',
            'reply_to_id' => 'nullable|uuid',
        ], [
            // Tùy chỉnh tin nhắn lỗi tiếng Việt
            'content.string' => 'Nội dung tin nhắn phải là chuỗi ký tự.',
            'media.file' => 'Tệp tin tải lên không hợp lệ.',
            'media.mimes' => 'Định dạng tệp không hỗ trợ (chỉ nhận ảnh và video).',
            'media.max' => 'Dung lượng tệp tối đa là 20MB.',
            'reply_to_id.uuid' => 'ID tin nhắn phản hồi không đúng định dạng.',
        ]);

        $userId = Auth::id();
        $message = $this->messageService->sendMessage(
            $userId,
            $conversationId,
            $request->input('content'),
            $request->file('media'),
            $request->input('reply_to_id')
        );

        return response()->json($message, 201);
    }
}