<?php

namespace App\Services;

use App\Repositories\CommentRepository\CommentInterface;
use Illuminate\Support\Facades\Auth;

class CommentService
{
    protected $commentRepository;

    public function __construct(CommentInterface $commentRepository)
    {
        $this->commentRepository = $commentRepository;
    }

    public function getCommentsByPost(string $postId, int $limit = 20)
    {
        $comments = $this->commentRepository->getParentCommentsByPost(
            $postId,
            $limit,
            ['user:user_id,name,avatar_url']
        );


        $comments->getCollection()->transform(function ($comment)  {
            // tổng số reply
            $totalReplies = $comment->replies()->count();
            // thêm thông tin bổ sung
            $comment->replies_count = $totalReplies;
            return $comment;
        });

        return $comments;
    }





    public function getReplies(string $commentId, int $limit = 5)
    {
        return $this->commentRepository->getByFieldWithPagination(
            'parent_id',
            $commentId,
            ['user:user_id,name,avatar_url'],
            $limit
        );
    }



    public function createComment(string $postId, string $content, ?string $parentId = null)
    {
        $userId = Auth::id();
        $comment = $this->commentRepository->create([
            'post_id' => $postId,
            'user_id' => $userId,
            'content' => $content,
            'parent_id' => $parentId,
        ]);
        $comment->load('user:user_id,name,avatar_url');
        return $comment;
        
    }

    public function deleteComment(string $commentId)
    {
        $userId = Auth::id();
        $comment = $this->commentRepository->find($commentId);

        if ($comment->user_id !== $userId) {
            throw new \Exception('Bạn không có quyền xoá comment này');
        }

        return $this->commentRepository->delete($commentId);
    }
}
