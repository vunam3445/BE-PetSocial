<?php

namespace App\Http\Controllers;

use App\Services\CommentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CommentController extends Controller
{
    protected $commentService;

    public function __construct(CommentService $commentService)
    {
        $this->commentService = $commentService;
    }

    /**
     * Lấy danh sách comment theo post
     */
    public function index(Request $request, string $postId)
    {
        $limit = $request->get('limit', 20);

        $comments = $this->commentService->getCommentsByPost($postId, $limit);

        return response()->json($comments);
    }

    /**
     * Tạo comment mới cho post
     */
    public function store(Request $request, string $postId)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        try {
            $comment = $this->commentService->createComment(
                $postId,
                $request->input('content'),
                $request->input('parent_id')
            );

            return response()->json($comment, 201);
        } catch (\Throwable $e) {
            Log::error("❌ Lỗi tạo comment: " . $e->getMessage());

            return response()->json([
                'message' => 'Không thể tạo comment',
            ], 500);
        }
    }

    /**
     * Xoá comment
     */
    public function destroy(string $commentId)
    {
        try {
            $this->commentService->deleteComment($commentId);

            return response()->json(true, 200);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 403); // forbidden nếu không phải chính chủ
        }
    }

    public function getReplies(string $commentId, Request $request)
    {
        $limit = $request->get('limit', 5);
        return response()->json(
            $this->commentService->getReplies($commentId, $limit)
        );
    }
}
