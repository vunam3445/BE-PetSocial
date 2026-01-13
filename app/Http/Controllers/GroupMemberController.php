<?php

namespace App\Http\Controllers;

use App\Services\GroupMemberService;
use Illuminate\Http\Request;

class GroupMemberController extends Controller
{
    protected $groupMemberService;
    public function __construct(GroupMemberService $groupMemberService)
    {
        $this->groupMemberService = $groupMemberService;
    }

    public function deleteMember(string $groupId, string $userId)
    {
        $res = $this->groupMemberService->deleteMember($groupId, $userId);
        return response()->json($res);
    }
}
