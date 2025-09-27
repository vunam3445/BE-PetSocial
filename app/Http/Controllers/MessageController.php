<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MessageService;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    protected $messageService;

    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }

    // Lấy danh sách tin nhắn trong 1 conversation
    public function index(string $conversationId)
    {
        $messages = $this->messageService->getMessages($conversationId);

        return response()->json($messages);
    }

    // Gửi tin nhắn
    public function store(Request $request, string $conversationId)
    {
        $request->validate([
            'content' => 'nullable|string',
            'media' => 'nullable|file|mimes:jpg,jpeg,png,gif,mp4,mov,avi|max:20480', // 20MB
            'reply_to_id' => 'nullable|uuid',
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

    // Đánh dấu đã đọc
    
}
