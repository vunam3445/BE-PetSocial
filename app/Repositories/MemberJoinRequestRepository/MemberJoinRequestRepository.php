<?php

namespace App\Repositories\MemberJoinRequestRepository;

use App\Repositories\Base\BaseRepository;
use App\Models\GroupJoinRequest;
use Illuminate\Support\Facades\DB;

class MemberJoinRequestRepository extends BaseRepository implements MemberJoinRequestInterface
{
    protected $model;
    public function __construct(GroupJoinRequest $model)
    {
        return parent::__construct($model);
    }

    public function deleteRequest(string $groupId, string $userId): bool
    {
        $request = $this->model
            ->where('group_id', $groupId)
            ->where('user_id', $userId)
            ->first();

        if (! $request) {
            return false;
        }

        return $request->delete();
    }


    public function getJoinRequests(string $groupId, int $limit = 20)
    {
        return DB::table('group_join_requests as r')
            ->where('r.group_id', $groupId)
            ->where('r.status','pending')
            ->leftJoin('users as u', 'r.user_id', '=', 'u.user_id')
            ->select(
                'r.id as request_id',
                'r.user_id',
                'u.name',
                'u.avatar_url',
                'r.status',
                'r.created_at'
            )
            ->orderBy('r.created_at', 'desc')
            ->paginate($limit)
            ->through(function ($item) use ($groupId) {

                // Lấy danh sách câu hỏi
                $questions = DB::table('group_join_questions')
                    ->where('group_id', $groupId)
                    ->get()
                    ->map(function ($q) use ($item) {

                        // Lấy answer của user
                        $answer = DB::table('group_join_answers')
                            ->where('request_id', $item->request_id)
                            ->where('question_id', $q->id)
                            ->value('answer');

                        return [
                            'question_id' => $q->id,
                            'question'    => $q->question,
                            'answer'      => $answer,
                        ];
                    });

                return [
                    'request_id' => $item->request_id,
                    'user_id'    => $item->user_id,
                    'name'       => $item->name,
                    'avatar_url' => $item->avatar_url,
                    'status'     => $item->status,
                    'created_at' => $item->created_at,
                    'questions'  => $questions,
                ];
            });
    }
}
