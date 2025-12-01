<?php

namespace App\Http\Controllers;

use App\Services\FollowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PetFollowController extends Controller
{
    protected FollowService $followService;

    public function __construct(FollowService $followService)
    {
        $this->followService = $followService;
    }   
     public function follow(Request $request, string $userId)
    {
        $this->followService->follow(Auth::id(), $userId, 'pet');
        return response()->json(['message' => 'Follow thành công']);
    }

    public function unfollow(Request $request, string $userId)
    {
        $this->followService->unfollow(Auth::id(), $userId, 'pet');
        return response()->json(['message' => 'Unfollow thành công']);
    }
}
