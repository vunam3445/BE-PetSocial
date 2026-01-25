<?php

namespace App\Services;

use App\Repositories\ConversationRepository\ConversationRepository;
use App\Repositories\MessageRepository\MessageRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use App\Models\Conversation;
use App\Notifications\ConversationInvitation;
use App\Notifications\ConversationKickNotification;
use Illuminate\Support\Facades\Storage;
use App\Notifications\ConversationDeletedNotification;

class ConversationService
{
    protected $conversationRepo;
    protected $messageRepo;

    public function __construct(
        ConversationRepository $conversationRepo,
        MessageRepository $messageRepo
    ) {
        $this->conversationRepo = $conversationRepo;
        $this->messageRepo = $messageRepo;
    }

    public function getUserConversations(string $userId, ?string $filterStatus = null)
{
    // Lấy dữ liệu từ Repo
    $conversations = $this->conversationRepo->getUserConversations($userId, $filterStatus);

    // Biến đổi dữ liệu
    $conversations->through(function ($conv) use ($userId) {
        // 1. Lấy tin nhắn mới nhất
        $latestMessage = $this->messageRepo->getLatestMessage($conv->conversation_id);

        // 2. Xác định trạng thái unread/read (Dấu chấm xanh)
        $lastReadAt = $conv->last_read_at;
        $latestMsgAt = $conv->latest_message_at;
        $uiStatus = ($latestMsgAt && (!$lastReadAt || $lastReadAt < $latestMsgAt)) ? 'unread' : 'read';

        // 3. Xử lý participants: CHỈ TRẢ VỀ NGƯỜI KIA NẾU LÀ PRIVATE CHAT
        if (!$conv->is_group) {
            // Lọc bỏ chính mình ra khỏi danh sách
            $otherUser = $conv->participants->filter(function ($p) use ($userId) {
                return $p->user_id !== $userId;
            });
            // Cập nhật lại danh sách chỉ chứa người kia
            $conv->setRelation('participants', $otherUser->values());
        } else {
            // Nếu là Group, bạn có thể giữ nguyên hoặc chỉ trả về chính mình tùy logic UI
            // Ở đây tôi giữ nguyên logic ẩn các trường thừa
        }

        // Clean up dữ liệu participant (ẩn các trường nhạy cảm/thừa)
        $conv->participants->each(function ($p) {
            $p->setVisible(['user_id', 'name', 'avatar_url']);
        });

        // 4. Trả về cấu trúc chuẩn
        return [
            'conversation'   => $conv,
            'latest_message' => $latestMessage,
            'status'         => $uiStatus,
        ];
    });

    return $conversations;
}





    public function markAsRead(string $conversationId, string $userId)
    {
        return $this->conversationRepo->updateParticipant($conversationId, $userId, [
            'last_read_at' => Carbon::now()
        ]);
    }
    public function createConversation(array $data)
    {
        $userId = Auth::id();
        $now = now();

        // 1. Kiểm tra chat đơn
        if (empty($data['is_group']) || !$data['is_group']) {
            $otherUserId = $data['participant_ids'][0];
            $existing = $this->conversationRepo->findPrivateConversation($userId, $otherUserId);

            if ($existing) {
                $existing->load(['participants' => function ($q) {
                    $q->select('users.user_id', 'name', 'avatar_url')->withPivot('role');
                }]);

                $me = $existing->participants->where('user_id', $userId)->first();
                // Gán attribute động
                $existing->my_role = $me ? $me->pivot->role : 'admin';
                return $existing;
            }
        }

        // 2. Tạo mới
        $isGroup = $data['is_group'] ?? false;
        $conversation = $this->conversationRepo->create([
            'name' => $data['name'] ?? null,
            'is_group' => $isGroup,
            'created_by' => $userId,
            'avatar_url' => $isGroup ? url('storage/avatars/default-group_avatar.png') : null,
        ]);

        // 3. Gán thành viên
        if ($isGroup) {
            $conversation->participants()->attach($userId, ['role' => 'admin', 'last_read_at' => $now]);
            $conversation->participants()->attach($data['participant_ids'], ['role' => 'member']);
            $currentRole = 'admin';
        } else {
            $conversation->participants()->attach([$userId, $data['participant_ids'][0]], ['role' => 'admin']);
            $currentRole = 'admin';
        }

        $conversation->load(['participants' => function ($q) {
            $q->select('users.user_id', 'name', 'avatar_url');
        }]);

        // Gán role trực tiếp vào object
        $conversation->my_role = $currentRole;

        return $conversation;
    }
    public function deleteConversation(string $conversationId)
    {
        $userId = Auth::id();
        return $this->conversationRepo->deleteConversationForUser($conversationId, $userId);
    }

    public function getRecentConversations(int $limit = 10)
    {
        $userId = Auth::id();
        return $this->conversationRepo->RecentConversations($userId, $limit);
    }

    public function getMemberOfConversation(string $conversationId)
    {
        return $this->conversationRepo->getMemberOfConversation($conversationId);
    }
    public function getMembersToInvite(string $conversationId)
    {
        $member = $this->conversationRepo->getMembersToInvite($conversationId);
        return $member;
    }
    public function addMembersToGroup(string $conversationId, array $userIds)
    {
        return DB::transaction(function () use ($conversationId, $userIds) {
            // 1. Kiểm tra quyền (Tùy chọn: Chỉ người tạo nhóm mới được thêm)
            $conversation = $this->conversationRepo->find($conversationId);
            if ($conversation->is_group && $conversation->created_by !== Auth::id()) {
                throw new \Exception("Bạn không có quyền thêm thành viên vào nhóm này.");
            }

            // 2. Thêm thành viên vào bảng trung gian
            $result = $this->conversationRepo->addParticipants($conversationId, $userIds);

            // 3. Nghiệp vụ mở rộng: Tạo tin nhắn hệ thống thông báo có người mới vào nhóm
            // $this->createSystemMessage($conversationId, $userIds);

            return $result;
        });
    }

    // Bước 1: Gửi lời mời (Chạy khi Admin nhấn "Gửi lời mời" từ Modal)
    public function inviteMembers(string $conversationId, array $userIds)
    {
        $conversation = Conversation::findOrFail($conversationId);
        $users = User::whereIn('user_id', $userIds)->get();
        $user = User::findOrFail(Auth::id());
        // Gửi thông báo đến danh sách user (Laravel sẽ tự lưu vào bảng notifications 
        // và chạy Job gửi Socket mà bạn đã viết)
        Notification::send($users, new ConversationInvitation($conversation, [
            'user_id' => $user->user_id,
            'name' => $user->name
        ]));

        return true;
    }

    public function handleInvitation(string $notificationId, string $status)
    {
        return DB::transaction(function () use ($notificationId, $status) {
            $userId = Auth::id();
            $user = User::findOrFail($userId);

            // Truy vấn qua $user để đảm bảo an toàn và dùng đúng quan hệ morph
            $notification = $user->customNotifications()->findOrFail($notificationId);
            // Lấy mảng data hiện tại (Laravel tự cast từ JSON sang array)
            $data = $notification->data;

            if ($status === 'accepted') {
                // 1. Thêm vào nhóm
                DB::table('conversation_participants')->insertOrIgnore([
                    'conversation_id' => $data['conversation_id'],
                    'user_id'         => $user->user_id,
                    'role'            => 'member',
                    'last_read_at'    => now(),
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);

                // 2. Cập nhật status trong data
                $data['status'] = 'accepted';
            } elseif ($status === 'rejected') {
                // Cập nhật status từ chối
                $data['status'] = 'rejected';
            } else {
                throw new \Exception("Trạng thái không hợp lệ.");
            }

            // 3. Lưu lại và đánh dấu đã đọc
            $notification->data = $data;
            $notification->save();
            $notification->markAsRead();

            return [
                'status' => $status,
                'conversation_id' => $data['conversation_id'] ?? null,
                'message' => $status === 'accepted' ? 'Đã tham gia nhóm' : 'Đã từ chối lời mời'
            ];
        });
    }
    public function deleteMember(string $conversationId, string $targetUserId)
    {
        return DB::transaction(function () use ($conversationId, $targetUserId) {
            $currentUserId = Auth::id();
            $currentUser = Auth::user();

            $conversation = Conversation::findOrFail($conversationId);
            if (!$conversation->is_group) {
                throw new \Exception("Chỉ có thể xóa thành viên khỏi cuộc trò chuyện nhóm.");
            }

            // Lấy thông tin role (bảng pivot) của cả người thực hiện và đối tượng bị tác động
            $participants = DB::table('conversation_participants')
                ->where('conversation_id', $conversationId)
                ->whereIn('user_id', [$currentUserId, $targetUserId])
                ->get();

            $currentUserPivot = $participants->where('user_id', $currentUserId)->first();
            $targetUserPivot = $participants->where('user_id', $targetUserId)->first();

            if (!$targetUserPivot) {
                throw new \Exception("Thành viên không tồn tại trong nhóm này.");
            }

            $isAdmin = $currentUserPivot && $currentUserPivot->role === 'admin';
            $isSelfAction = ($currentUserId === $targetUserId);

            // --- BẮT ĐẦU PHẦN KIỂM TRA MỚI ---
            // Nếu là Admin và đang tự xóa bản thân (Rời nhóm)
            if ($isAdmin && $isSelfAction) {
                throw new \Exception("Bạn là quản trị viên. Vui lòng chuyển quyền quản lý cho người khác trước khi rời nhóm.");
            }
            // --- KẾT THÚC PHẦN KIỂM TRA MỚI ---

            // Kiểm tra quyền hạn chung
            if (!$isAdmin && !$isSelfAction) {
                throw new \Exception("Bạn không có quyền xóa thành viên này.");
            }

            // Thực hiện xóa
            DB::table('conversation_participants')
                ->where('conversation_id', $conversationId)
                ->where('user_id', $targetUserId)
                ->delete();

            // Gửi thông báo nếu Admin xóa thành viên khác
            if ($isAdmin && !$isSelfAction) {
                $targetUser = User::find($targetUserId);
                if ($targetUser) {
                    Notification::send($targetUser, new ConversationKickNotification($conversation, $currentUser->name));
                }
            }

            return [
                'status' => 'success',
                'type' => $isSelfAction ? 'leave' : 'remove',
                'message' => $isSelfAction ? 'Bạn đã rời nhóm.' : 'Đã xóa thành viên khỏi nhóm.'
            ];
        });
    }

    public function updateConversation(string $id, array $data)
    {
        $updateData = [];

        // 1. Cập nhật name
        if (!empty($data['name'])) {
            $updateData['name'] = $data['name'];
        }

        // 2. Xử lý lưu file và tạo Full URL
        if (isset($data['avatar']) && $data['avatar'] instanceof \Illuminate\Http\UploadedFile) {

            // Xử lý xóa file cũ trước khi lưu file mới
            $current = $this->conversationRepo->find($id);
            if ($current->avatar_url) {
                // Lấy tên file từ URL cũ để xóa trong storage
                $oldFileName = basename($current->avatar_url);
                if ($oldFileName !== 'default-group_avatar.png') {
                    Storage::disk('public')->delete('avatars/' . $oldFileName);
                }
            }

            // Lưu file mới vào: storage/app/public/avatars
            $path = $data['avatar']->store('avatars', 'public');

            // Tạo định dạng: http://127.0.0.1:8000/storage/avatars/ten_file.ext
            $updateData['avatar_url'] = asset('storage/' . $path);
        }

        if (empty($updateData)) {
            return null;
        }

        return $this->conversationRepo->update($id, $updateData);
    }

    public function changeRole(string $conversationId, string $tagetId)
    {
        return $this->conversationRepo->changeRole($conversationId, $tagetId);
    }

    public function delete(string $conversationId)
{
    return DB::transaction(function () use ($conversationId) {
        $user = Auth::user(); // Lấy cả object user để lấy tên
        $conversation = Conversation::with('participants')->findOrFail($conversationId);

        $recipients = $conversation->participants->filter(fn($u) => $u->user_id !== $user->user_id);

        if ($recipients->isNotEmpty()) {
            foreach ($recipients as $recipient) {
                $currentDomain = request()->getSchemeAndHttpHost();
                $recipient->notify(new ConversationDeletedNotification($conversation, $user->name, $currentDomain));
            }
        }

        $conversation->delete();

        return [
            'status' => 'success',
            'message' => $conversation->is_group ? 'Đã giải tán nhóm.' : 'Đã xóa hội thoại.'
        ];
    });
}
}
