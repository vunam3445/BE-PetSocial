<?php

namespace App\Services;

use App\Repositories\GroupRepository\GroupRepository;
use App\Repositories\MemberJoinRequestRepository\MemberJoinRequestRepository;
use App\Repositories\MembershipQuestion\MembershipRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use League\Flysystem\Visibility;

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
    public function createRequest(array $data)
    {
        $groupId = $data['groupId'];
        $userId  = $data['userId'];
        $answers = $data['answers'] ?? null; // answers = [question_id => answer]

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
        foreach ($answers as $questionId => $answerText) {
            $this->membershipRepository->createJoinAnswer([
                'request_id'  => $request->id,
                'question_id' => $questionId,
                'answer'      => $answerText
            ]);
        }

        return $request;
    }

    public function updateRequest(int $id, $data)
    {
        return $this->memberJoinRequest->update($id, $data);
    }
    public function deleteRequest(int $id)
    {
        return $this->memberJoinRequest->delete($id);
    }
    public function approveMember(string $userId, string $groupId)
    {
        return $this->groupRepository->addMemberToGroup($groupId, [$userId], 'member');
    }
}
