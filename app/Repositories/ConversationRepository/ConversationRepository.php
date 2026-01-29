<?php

namespace App\Repositories\ConversationRepository;

use App\Repositories\Base\BaseRepository;
use App\Models\Conversation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class ConversationRepository extends BaseRepository implements ConversationInterface
{
    public function __construct(Conversation $model)
    {
        parent::__construct($model);
    }

    public function getUserConversations(string $userId, ?string $filterStatus = null)
    {
        $query = $this->model
            ->where(function ($q) {
                $q->where('is_group', true) // Nếu là group thì lấy luôn (dù chưa có tin nhắn)
                    ->orWhere(function ($sub) {
                        $sub->where('is_group', false)
                            ->has('messages'); // Nếu là private chat thì BẮT BUỘC phải có tin nhắn
                    });
            })
            ->whereHas('participants', function ($q) use ($userId) {
                $q->where('conversation_participants.user_id', $userId);
            })
            ->with(['participants' => function ($q) {
                $q->withPivot('role', 'last_read_at', 'last_reply_at');
            }]);
        // Subquery lấy thời gian tin nhắn mới nhất
        $latestMessageSql = "(SELECT created_at FROM messages WHERE messages.conversation_id = conversations.conversation_id ORDER BY created_at DESC LIMIT 1)";
        $sendersCountSql = "(SELECT COUNT(DISTINCT sender_id) FROM messages WHERE messages.conversation_id = conversations.conversation_id)";
        // Join lấy dữ liệu pivot của CHÍNH user đang gọi để lọc/sort
        $query->join('conversation_participants', 'conversations.conversation_id', '=', 'conversation_participants.conversation_id')
            ->where('conversation_participants.user_id', $userId)
            ->select(
                'conversations.*',
                'conversation_participants.last_read_at',
                'conversation_participants.last_reply_at',
                'conversation_participants.role as my_role' // Lấy role của tôi ra ngoài để dễ dùng
            );
        $query->selectSub($sendersCountSql, 'senders_count'); // Thêm cột này vào kết quả
        $query->selectSub($latestMessageSql, 'latest_message_at');

        if ($filterStatus === 'unread') {
            $query->where(function ($q) {
                $q->where(function ($sub) {
                    // Private chat: chưa phản hồi (không quan tâm đã đọc hay chưa theo logic của bạn)
                    $sub->where('conversations.is_group', false)
                        ->whereNull('conversation_participants.last_reply_at');
                })->orWhere(function ($sub) {
                    // Group chat: chưa đọc
                    $sub->where('conversations.is_group', true)
                        ->whereNull('conversation_participants.last_read_at');
                });
            });
        } elseif ($filterStatus === 'read') {
            $query->where(function ($q) {
                $q->where(function ($sub) {
                    // Private chat: đã phản hồi
                    $sub->where('conversations.is_group', false)
                        ->whereNotNull('conversation_participants.last_reply_at');
                })->orWhere(function ($sub) {
                    // Group chat: đã đọc
                    $sub->where('conversations.is_group', true)
                        ->whereNotNull('conversation_participants.last_read_at');
                });
            });
        }

        $results = $query->orderByRaw("COALESCE(($latestMessageSql), conversations.created_at) DESC")
            ->paginate(15);

        // Xử lý biến đổi Collection mà vẫn giữ Object Participant là Model
        $results->getCollection()->transform(function ($conversation) use ($userId) {
            if ($conversation->is_group) {
                // Chỉ giữ lại chính mình trong danh sách participants
                $me = $conversation->participants->firstWhere('user_id', $userId);

                if ($me) {
                    // Gán role vào object để trả về cùng cấp với name
                    $me->role = $me->pivot->role;
                    // Ẩn các trường không cần thiết nhưng vẫn giữ object là Model
                    $me->makeHidden(['pivot', 'email', 'created_at', 'updated_at', 'email_verified_at']);

                    $conversation->setRelation('participants', collect([$me]));
                }
            } else {
                // Chat đơn: Trả về đầy đủ nhưng gán thêm role từ pivot nếu cần
                $conversation->participants->each(function ($p) {
                    $p->role = $p->pivot->role;
                    $p->makeHidden(['pivot']);
                });
            }
            return $conversation;
        });

        return $results;
    }
    public function updateParticipant($conversationId, $userId, array $data)
    {
        return DB::table('conversation_participants')
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->update([
            'last_read_at' => now(), // Đảm bảo dùng helper now() của Laravel để lấy giờ server
            'updated_at' => now()
        ]);
    }
    public function findPrivateConversation(string $userId, string $otherUserId)
    {
        return $this->model
            ->where('is_group', false)
            ->whereHas('participants', fn($q) => $q->where('conversation_participants.user_id', $userId))
            ->whereHas('participants', fn($q) => $q->where('conversation_participants.user_id', $otherUserId))
            ->first();
    }
    public function markOthersUnread($conversationId, $senderId)
    {
        return DB::table('conversation_participants')
            ->where('conversation_id', $conversationId)
            ->where('user_id', '!=', $senderId)
            ->update([
                'last_reply_at' => null // hoặc giữ nguyên, tuỳ bạn muốn flag unread bằng cách nào
            ]);
    }
    public function markAllRead($conversationId)
    {
        return DB::table('conversation_participants')
            ->where('conversation_id', $conversationId)
            ->update([
                'last_reply_at' => now()
            ]);
    }
    public function RecentConversations($userId, $limit = 10)
    {
        return $this->model
            ->where('is_group', false) // chỉ lấy 1-1
            ->whereHas('participants', function ($q) use ($userId) {
                $q->where('conversation_participants.user_id', $userId);
            })
            ->with([
                'participants' => function ($q) use ($userId) {
                    $q->where('conversation_participants.user_id', '!=', $userId)
                        ->select('users.user_id', 'users.name', 'users.avatar_url');
                }
            ])
            ->latest() // sắp xếp theo thời gian tạo conversation
            ->limit($limit)
            ->get();
    }

    public function deleteConversationForUser(string $conversationId, string $userId)
    {
        return DB::table('conversation_participants')
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->update(['last_read_at' => null]); // Reset về null để ép nó thành unread
    }

    public function getMemberOfConversation(string $conversationId)
    {
        // Tìm conversation và load quan hệ participants (User model)
        $conversation = $this->model->with(['participants' => function ($query) {
            // Bạn có thể chọn các field cần thiết từ bảng users
            // Và lấy thêm thông tin 'role' từ bảng trung gian (pivot)
            $query->select('users.user_id', 'users.name', 'users.avatar_url')
                ->withPivot('role');
        }])->find($conversationId);

        if (!$conversation) {
            return collect([]); // Trả về collection rỗng nếu không tìm thấy
        }

        // Trả về danh sách các User (Participants)
        return $conversation->participants;
    }

    public function getMembersToInvite(string $conversationId, int $limit = 15)
    {
        $currentUserId = Auth::id();

        return User::where('user_id', '!=', $currentUserId)
            ->where(function ($query) use ($currentUserId) {
                $query->whereIn('user_id', function ($q) use ($currentUserId) {
                    // Lấy ID từ những người đã từng nhắn tin
                    $q->select('p2.user_id')
                        ->from('conversation_participants as p1')
                        ->join('conversation_participants as p2', 'p1.conversation_id', '=', 'p2.conversation_id')
                        ->where('p1.user_id', $currentUserId)
                        ->where('p2.user_id', '!=', $currentUserId);
                })
                    ->orWhereIn('user_id', function ($q) use ($currentUserId) {
                        // Lấy ID từ những người đang follow
                        $q->select('followed_id')
                            ->from('follows')
                            ->where('follower_id', $currentUserId);
                    });
            })
            ->whereDoesntHave('conversations', function ($q) use ($conversationId) {
                // Loại bỏ những người đã ở trong nhóm hiện tại
                $q->where('conversation_participants.conversation_id', $conversationId);
            })
            ->select('user_id', 'name', 'avatar_url', 'email')
            ->paginate($limit); // Sử dụng paginate thay vì get
    }

    public function addParticipants(string $conversationId, array $userIds)
    {
        $conversation = $this->model->findOrFail($conversationId);

        // Chuẩn bị dữ liệu cho bảng trung gian (pivot)
        $pivotData = [];
        foreach ($userIds as $userId) {
            $pivotData[$userId] = [
                'role' => 'member',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // syncWithoutDetaching giúp thêm mới mà không xóa các thành viên cũ
        return $conversation->participants()->syncWithoutDetaching($pivotData);
    }

    public function changeRole(string $conversationId, string $targetId)
    {
        // Lấy ID của người dùng hiện tại (người đang thực hiện lệnh)
        $myId = Auth::id();

        // Sử dụng Transaction để đảm bảo tính toàn vẹn dữ liệu
        return DB::transaction(function () use ($conversationId, $targetId, $myId) {

            // 1. Cập nhật người được chọn (targetId) lên Admin
            $updatedTarget = DB::table('conversation_participants')
                ->where('conversation_id', $conversationId)
                ->where('user_id', $targetId)
                ->update([
                    'role' => 'admin',
                    'updated_at' => now()
                ]);

            // Nếu không tìm thấy targetId trong cuộc hội thoại này, hủy transaction
            if ($updatedTarget === 0) {
                throw new \Exception('Không tìm thấy người dùng mục tiêu trong cuộc hội thoại.');
            }

            // 2. Cập nhật bản thân (myId) xuống Member
            $updatedMe = DB::table('conversation_participants')
                ->where('conversation_id', $conversationId)
                ->where('user_id', $myId)
                ->update([
                    'role' => 'member',
                    'updated_at' => now()
                ]);

            if ($updatedMe === 0) {
                throw new \Exception('Bạn không phải là thành viên của cuộc hội thoại này.');
            }

            return [
                'status' => 'success',
                'message' => 'Chuyển quyền quản trị viên thành công.'
            ];
        });
    }
    public function getUnreadConversationsCount(string $userId)
{
    return $this->model
        ->whereHas('participants', function ($query) use ($userId) {
            $query->where('conversation_participants.user_id', $userId)
                ->where(function ($q) use ($userId) {
                    
                    // Điều kiện chung: Phải tồn tại tin nhắn của NGƯỜI KHÁC gửi sau lần mình đọc cuối
                    $unreadCondition = function ($sub) use ($userId) {
                        $sub->select(DB::raw(1))
                            ->from('messages')
                            ->whereColumn('messages.conversation_id', 'conversations.conversation_id')
                            ->where('messages.sender_id', '!=', $userId) // QUAN TRỌNG: Không đếm tin của chính mình
                            ->where(function ($timeCheck) {
                                // So sánh thời gian tin nhắn > thời gian đọc cuối
                                $timeCheck->whereColumn('messages.created_at', '>', 'conversation_participants.last_read_at')
                                          ->orWhereNull('conversation_participants.last_read_at');
                            });
                    };

                    // --- TRƯỜNG HỢP 1: CHAT NHÓM ---
                    $q->where(function ($groupQuery) use ($unreadCondition) {
                        $groupQuery->where('conversations.is_group', true)
                                   ->whereExists($unreadCondition);
                    })
                    
                    // --- TRƯỜNG HỢP 2: CHAT 1-1 ---
                    ->orWhere(function ($privateQuery) use ($unreadCondition) {
                        $privateQuery->where('conversations.is_group', false)
                            // Phải có ít nhất 2 người nhắn tin (đã tương tác)
                            ->whereRaw("(SELECT COUNT(DISTINCT sender_id) FROM messages WHERE messages.conversation_id = conversations.conversation_id) >= 2")
                            // Và có tin nhắn của người kia chưa xem
                            ->whereExists($unreadCondition);
                    });
                });
        })
        ->count();
}
}
