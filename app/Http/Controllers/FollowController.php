<?php

namespace App\Http\Controllers;

use App\Services\FollowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FollowController extends Controller
{
    protected FollowService $followService;

    public function __construct(FollowService $followService)
    {
        $this->followService = $followService;
    }

    public function follow(Request $request, string $userId)
    {
        $this->followService->follow(Auth::id(), $userId);
        return response()->json(['message' => 'Follow thành công']);
    }

    public function unfollow(Request $request, string $userId)
    {
        $this->followService->unfollow(Auth::id(), $userId);
        return response()->json(['message' => 'Unfollow thành công']);
    }

    public function followers(string $userId)
    {
        return $this->followService->getFollowers($userId);
    }

    public function following(string $userId)
    {
        return $this->followService->getFollowing($userId);
    }
}
