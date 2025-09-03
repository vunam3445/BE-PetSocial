<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\LikeService;
class LikeController extends Controller
{
    protected $likeService;
    public function __construct(LikeService $likeService)
    {
        $this->likeService = $likeService;
    }



    public function getLikesForPost(string $postId)
    {
        return $this->likeService->getLikesForPost($postId);
    }
    public function toggle(string $postId)
    {

        $liked = $this->likeService->toggleLike($postId);

        return response()->json($liked);
    }

}
