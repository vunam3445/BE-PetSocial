<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ConversationService;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\ConversationResource;

class ConversationController extends Controller
{
    protected $conversationService;

    public function __construct(ConversationService $conversationService)
    {
        $this->conversationService = $conversationService;
    }

    // Lấy danh sách cuộc trò chuyện của user
    public function index(Request $request)
    {
        $userId = Auth::id();
        $filterStatus = $request->query('status');

        $conversations = $this->conversationService->getUserConversations($userId, $filterStatus);

        return response()->json($conversations);
    }

    // Tạo cuộc trò chuyện mới
    public function store(Request $request)
    {
        $request->validate([
            'participant_ids' => 'required|array|min:1',
        ]);

        $userId = Auth::id();
        $conversation = $this->conversationService->createConversation([
            'participant_ids' => $request->participant_ids,
        ]);

        return new ConversationResource($conversation);
    }



    // Xóa cuộc trò chuyện
    public function destroy(string $id)
    {
        $this->conversationService->deleteConversation($id);

        return response()->json(['message' => 'Conversation deleted']);
    }
    public function update(string $conversationId)
    {
        $userId = Auth::id();
        $this->conversationService->markAsRead($userId, $conversationId);

        return response()->json(['message' => 'Marked as read']);
    }

    public function recent(Request $request)
    {
        $limit = $request->query('limit', 10);

        $conversations = $this->conversationService->getRecentConversations($limit);

        return response()->json($conversations);
    }
}
