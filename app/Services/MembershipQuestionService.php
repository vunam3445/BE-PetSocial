<?php

namespace App\Services;

use App\Repositories\GroupRepository\GroupRepository;
use App\Repositories\MemberJoinRequestRepository\MemberJoinRequestRepository;
use App\Repositories\MembershipQuestion\MembershipRepository;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Group;
use App\Notifications\GroupJoinedNotification;
use App\Notifications\GroupRejectedNotification;

class MembershipQuestionService
{
    protected $membershipRepository;
    protected $memberJoinRequest;
    protected $groupRepository;
    public function __construct(MembershipRepository $membershipRepository, MemberJoinRequestRepository $memberJoinRequest, GroupRepository $groupRepository)
    {
        $this->membershipRepository = $membershipRepository;
        $this->memberJoinRequest = $memberJoinRequest;
        $this->groupRepository = $groupRepository;
    }

    public function create(array $data)
    {
        return $this->membershipRepository->create($data);
    }
    public function update(string $id, array $data)
    {
        return $this->membershipRepository->update($id, $data);
    }
    public function delete(string $id)
    {
        return $this->membershipRepository->delete($id);
    }
    public function getQuestionOfGroup(string $groupId)
    {
        return $this->membershipRepository->getByField('group_id', $groupId, [], []);
    }
    public function createRequest(string $groupId, array $answers)
    {

        $userId  = Auth::id();

        $group = $this->groupRepository->find($groupId);

        // 1) PUBLIC GROUP → Add member directly
        if ($group->visibility === 'public') {
            return $this->groupRepository->addMemberToGroup($groupId, [$userId], 'member');
        }

        // 2) PRIVATE GROUP → Must have membership answers
        if (!$answers || !is_array($answers)) {
            throw new \Exception("Bạn cần trả lời các câu hỏi để tham gia nhóm.");
        }

        // 2.1 – Tạo bản ghi group_join_requests
        $request = $this->memberJoinRequest->create([
            'group_id' => $groupId,
            'user_id'  => $userId,
            'status'   => 'pending'
        ]);

        // 2.2 – Tạo bản ghi group_join_answers cho từng câu hỏi
        foreach ($answers as $item) {
            $this->membershipRepository->createJoinAnswer([
                'request_id'  => $request->id,
                'question_id' => $item['question_id'],
                'answer'      => $item['answer'],
                'user_id'     => $userId,
            ]);
        }

        return $request;
    }

    public function updateRequest(int $id, $data)
    {
        $groupId = $data['group_id'] ?? null;
        $userId = $data['user_id'] ?? null;
        $status = $data['status'];
        $user = User::find($userId);
        $group = Group::find($groupId);

        if ($status == 'approved') {
            $res = $this->memberJoinRequest->update($id, ['status' => $status]);
            $this->groupRepository->addMemberToGroup($groupId, [$userId], 'member');
            // 3. Lấy thông tin để gửi thông báo

            if ($user && $group) {
                // GỌI TRỰC TIẾP TẠI ĐÂY
                $user->notify(new GroupJoinedNotification($group));
            }
        }
        if ($status === 'rejected') {
            $res = $this->memberJoinRequest->delete($id);
            if ($group && $user) {
                $user->notify(new GroupRejectedNotification($group));
            }
        }
        return $res;
    }
    public function deleteRequest(string $groupId)
    {
        $userId = Auth::id();
        return $this->memberJoinRequest->deleteRequest($groupId, $userId);
    }
    public function approveMember(string $userId, string $groupId)
    {
        return $this->groupRepository->addMemberToGroup($groupId, [$userId], 'member');
    }

    public function getJoinRequests(string $groupId)
    {
        return $this->memberJoinRequest->getJoinRequests($groupId, 20);
    }
}
