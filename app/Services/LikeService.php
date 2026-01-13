<?php

namespace App\Services;

use App\Repositories\LikeRepository\LikeInterface;
use Illuminate\Support\Facades\Auth;
use App\Models\Like;
use Illuminate\Support\Facades\DB;
class LikeService
{
    protected $likeRepository;

    public function __construct(LikeInterface $likeRepository)
    {
        $this->likeRepository = $likeRepository;
    }



    public function getLikesForPost(string $postId)
    {
        return $this->likeRepository->getLikesForPost($postId);
    }
   public function toggleLike(string $postId): bool
{
    $userId = Auth::id();
    $like = Like::where('user_id', $userId)->where('post_id', $postId)->first();

    if ($like) {
        // --- LOGIC XỬ LÝ THỦ CÔNG KHI UNLIKE (Thay thế cho Deleted Observer) ---
        
        // 1. Tìm Interaction cũ để lấy danh sách tags cần trừ điểm
        $interaction = \App\Models\UserInteraction::where('user_id', $userId)
            ->where('post_id', $postId)
            ->where('action_type', 'like')
            ->first();

        if ($interaction) {
            $metadata = is_string($interaction->metadata) 
                ? json_decode($interaction->metadata, true) 
                : $interaction->metadata;
            
            $tags = $metadata['tags'] ?? [];

            foreach ($tags as $tagName) {
                // Trừ điểm sở thích, dùng GREATEST để không bao giờ bị điểm âm
                DB::table('user_interest_profiles')
                    ->where('user_id', $userId)
                    ->where('tag_name', strtolower(trim($tagName)))
                    ->update([
                        'score' => DB::raw("GREATEST(0, score - 1)"),
                        'updated_at' => now()
                    ]);
            }
            // 2. Xóa bản ghi interaction để Python không quét lại bài đã Unlike
            $interaction->delete();
        }

        // 3. Thực hiện xóa bản ghi Like gốc (Trigger Unlike)
        $like->delete(); 
        
        return false; 
    }

    // --- LOGIC KHI LIKE ---
    // Phần này giữ nguyên vì LikeObserver@created của bạn đã hoạt động
    Like::create([
        'user_id' => $userId,
        'post_id' => $postId,
    ]);

    return true; 
}

    public function countLikes(string $postId): int
    {
        return Like::where('post_id', $postId)->count();
    }
}
