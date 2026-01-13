<?php

namespace App\Repositories\SearchRepository;

use App\Models\Conversation;
use App\Models\User;
use App\Models\Post;
use App\Models\Group;
use App\Repositories\SearchRepository\SearchInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\GroupJoinRequest;

class SearchRepository implements SearchInterface
{
    public function searchUsers(string $keyword, int $perPage = 10)
    {
        $authId = Auth::id();

        return User::withCount('followers')
            ->withExists(['followers as is_followed' => function ($query) use ($authId) {
                $query->where('follower_id', $authId);
            }])
            ->where('user_id', '!=', $authId) // loại bỏ chính mình
            ->where(function ($query) use ($keyword) {
                $query->where('name', 'ILIKE', "%{$keyword}%")
                    ->orWhere('email', 'ILIKE', "%{$keyword}%");
            })
            ->paginate($perPage, [
                'user_id',
                'name',
                'avatar_url',
                'bio',
            ]);
    }



    public function searchPosts(string $keyword, int $perPage = 10)
    {
        $query = Post::where(function ($q) use ($keyword) {
            // Tìm trong caption
            $q->where('caption', 'ILIKE', "%{$keyword}%")
                // Tìm trong tags
                ->orWhereHas('tags', function ($tagQuery) use ($keyword) {
                    $tagQuery->where('name', 'ILIKE', "%{$keyword}%");
                });
        })
            ->with([
                'author:user_id,name,avatar_url',
                'sharedPost.media',
                'sharedPost.author:user_id,name,avatar_url',
                'sharedPost.tags',
                'tags',
                'media',
            ]);

        // Đếm like và comment
        $query->withCount(['comments', 'likes']);

        // Nếu có user đăng nhập thì thêm cột is_liked
        $userId = Auth::id();
        if ($userId) {
            $query->withCount([
                'likes as is_liked' => function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                }
            ]);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }


    public function searchPets(string $keyword, int $perPage = 10)
    {
        $authId = Auth::id();

        return \App\Models\Pet::where(function ($query) use ($keyword) {
            $query->where('name', 'ILIKE', "%{$keyword}%")
                ->orWhere('breed', 'ILIKE', "%{$keyword}%")
                ->orWhere('type', 'ILIKE', "%{$keyword}%");
        })
            ->where('user_id', '!=', $authId)
            ->select([
                'pet_id',
                'name',
                'avatar_url',
                'breed',
                'type',
                'user_id as owner_id',
                DB::raw("EXISTS (
                SELECT 1 
                FROM follows 
                WHERE follows.followed_id = pets.user_id
                AND follows.follower_id = '{$authId}'
            ) as is_following")
            ])
            ->paginate($perPage);
    }
    public function searchGroups(string $keyword, int $perPage = 10)
    {
        $authId = Auth::id();

        return Group::query()
            ->withCount(['members as members_count', 'posts as posts_count'])
            ->withExists([
                'members as is_member' => function ($q) use ($authId) {
                    $q->where('users.user_id', $authId);
                }
            ])
            ->where(function ($q) use ($keyword) {
                $q->where('name', 'ILIKE', "%{$keyword}%");
            })
            ->paginate($perPage);
    }

    public function searchJoinGroupRequest(
        string $groupId,
        string $keyword,
        int $limit = 20
    ) {
        return DB::table('group_join_requests as r')
            ->where('r.group_id', $groupId)
            ->where('r.status', 'pending')

            // 🔍 Search theo user
            ->where(function ($query) use ($keyword) {
                $query->where('u.name', 'ILIKE', "%{$keyword}%");
            })

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

                        // Lấy answer tương ứng
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


    public function searchUsersOfGroup(string $groupId, string $keyword, int $limit = 20)
    {
        return DB::table('group_members')
            ->join('users', 'users.user_id', '=', 'group_members.user_id')
            ->where('group_members.group_id', $groupId)
            ->when($keyword, function ($query) use ($keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('users.name', 'ILIKE', "%{$keyword}%");
                });
            })
            ->select([
                'users.user_id',
                'users.name',
                'users.avatar_url',
                'group_members.role',
                'group_members.joined_at',
            ])
            ->orderBy('group_members.joined_at', 'desc')
            ->paginate($limit);
    }

    public function searchConversation(string $keyword, int $limit = 15)
{
    $userId = Auth::id();

    // 1. Thực hiện truy vấn tìm kiếm cơ bản
    $paginatedResults = Conversation::whereHas('participantDetails', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
        ->where(function ($query) use ($keyword, $userId) {
            $query->where('name', 'like', '%' . $keyword . '%')
                ->orWhereHas('participantDetails', function ($detailQuery) use ($keyword, $userId) {
                    $detailQuery->where('user_id', '!=', $userId)
                        ->whereHas('user', function ($userQuery) use ($keyword) {
                            $userQuery->where('name', 'like', '%' . $keyword . '%');
                        });
                });
        })
        ->with([
            'participants' => function ($q) {
                // Lấy thêm last_read_at từ bảng trung gian giống hàm get
                $q->withPivot('last_read_at');
            }
        ])
        ->orderBy('updated_at', 'desc')
        ->paginate($limit);

    // 2. Transform dữ liệu bên trong Paginate Collection để giống cấu trúc hàm get
    $paginatedResults->getCollection()->transform(function ($conv) use ($userId) {
        // Lấy tin nhắn mới nhất (nên tối ưu bằng eager loading nếu có thể, 
        // nhưng ở đây giữ logic giống hàm get cũ của bạn)
        $latestMessage = $conv->messages()->latest()->first();

        $currentUserPivot = $conv->participants->where('user_id', $userId)->first()?->pivot;
        $lastReadAt = $currentUserPivot ? $currentUserPivot->last_read_at : null;

        // Tính toán status giống hệt hàm get
        if (!$lastReadAt) {
            $status = 'unread';
        } elseif (!$latestMessage) {
            $status = 'read';
        } else {
            $status = ($lastReadAt < $latestMessage->created_at) ? 'unread' : 'read';
        }

        // Làm sạch participants (chỉ giữ lại các field cần thiết và ẩn pivot)
        $conv->participants->each(function ($p) {
            $p->setVisible(['user_id', 'name', 'avatar_url']);
            $p->makeHidden('pivot'); 
        });

        // Trả về cấu trúc bọc (wrapper) giống hàm get
        return [
            'conversation'   => $conv,
            'latest_message' => $latestMessage,
            'status'         => $status,
        ];
    });

    return $paginatedResults;
}
}
