<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\GroupService;
use App\Http\Requests\CreateGroupRequest;
use App\Models\Group;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class GroupController extends Controller
{
    use AuthorizesRequests; 

    protected $groupService;
    public function __construct(GroupService $groupService)
    {
        $this->groupService = $groupService;
    }
    public function createGroup(Request $request)
    {
        $data = $request->only(['name', 'visibility', 'created_by']);
        $group = $this->groupService->createGroup($data);
        return response()->json($group, 201);
    }
    public function getGroupById($id)
    {    
        $group = $this->groupService->getGroupInfo($id);
        if ($group) {
            return response()->json($group);
        } else {
            return response()->json(['message' => 'Group not found'], 404);
        }
    }
    public function updateGroup(Request $request, Group $group)
    {
        $this->authorize('update', $group);
        $data = $request->all();
        $updatedGroup = $this->groupService->updateGroup($group, $data);
        if ($updatedGroup) {
            return response()->json($updatedGroup);
        } else {
            return response()->json(['message' => 'Group not found or update failed'], 404);
        }
    }
    public function deleteGroup(Group $group)
    {
        $this->authorize('delete', $group);
        $deleted = $this->groupService->deleteGroup($group);
        if ($deleted) {
            return response()->json(['message' => 'Group deleted successfully']);
        } else {
            return response()->json(['message' => 'Group not found or delete failed'], 404);
        }
    }
    public function getGroupsByUserId($userId)
    {
        $groups = $this->groupService->getGroupsByUserId($userId);
        return response()->json($groups);
    }
    public function getGroupOfUserAttended($userId)
    {
        $groups = $this->groupService->getGroupOfUserAttended($userId);
        return response()->json($groups);
    }

    public function addMemberToGroup(Request $request, $groupId)
    {
        $data = $request->only(['user_ids', 'role']);
        $this->groupService->addMemberToGroup($groupId, $data['user_ids'], $data['role']);
        return response()->json(['message' => 'Members added successfully']);
    }

    public function getMembers(Request $request, $groupId) {
        $members = $this->groupService->getMembers($groupId);
        return response()->json($members);
    }

}
