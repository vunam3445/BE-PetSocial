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
        $query = $this->model->whereHas('participants', function ($q) use ($userId) {
            $q->where('conversation_participants.user_id', $userId);
        })
            ->with(['participants' => function ($q) {
                $q->withPivot('last_read_at', 'last_reply_at');
            }]);

        // Subquery lấy thời gian tin nhắn mới nhất để sắp xếp
        $latestMessageSql = "(SELECT created_at FROM messages WHERE messages.conversation_id = conversations.conversation_id ORDER BY created_at DESC LIMIT 1)";

        // Join để lấy dữ liệu từ bảng pivot của chính user này
        $query->join('conversation_participants', 'conversations.conversation_id', '=', 'conversation_participants.conversation_id')
            ->where('conversation_participants.user_id', $userId)
            ->select('conversations.*', 'conversation_participants.last_read_at', 'conversation_participants.last_reply_at');

        $query->selectSub($latestMessageSql, 'latest_message_at');

        // LOGIC LỌC THEO ĐÍNH CHÍNH CỦA BẠN:
        if ($filterStatus === 'unread') {
            // Tab Unread: Chỉ lấy Chat đơn (is_group = false) VÀ chưa phản hồi (last_reply_at IS NULL)
            $query->where('conversations.is_group', false)
                ->whereNull('conversation_participants.last_reply_at');
        } elseif ($filterStatus === 'read') {
            // Tab Read: Lấy tất cả Group HOẶC Chat đơn đã phản hồi
            $query->where(function ($q) {
                $q->where('conversations.is_group', true)
                    ->orWhereNotNull('conversation_participants.last_reply_at');
            });
        }

        // Sắp xếp: Ưu tiên tin nhắn mới nhất, nếu chưa có tin nhắn thì dùng created_at của hội thoại
        return $query->orderByRaw("COALESCE(($latestMessageSql), conversations.created_at) DESC")
            ->paginate(15);
    }
    public function updateParticipant($conversationId, $userId, array $data)
    {
        return DB::table('conversation_participants')
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->update($data);
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
}
