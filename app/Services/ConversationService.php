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
        // Lấy dữ liệu đã được lọc đúng số lượng 15 items/page từ SQL
        $conversations = $this->conversationRepo->getUserConversations($userId, $filterStatus);

        // Biến đổi dữ liệu (vẫn giữ nguyên object Paginator)
        $conversations->through(function ($conv) use ($userId) {
            // 1. Lấy tin nhắn mới nhất để hiển thị nội dung chat trên UI
            $latestMessage = $this->messageRepo->getLatestMessage($conv->conversation_id);

            // 2. Xác định trạng thái để hiển thị "Dấu chấm xanh" (unread) trên UI
            // Đây là trạng thái ĐÃ ĐỌC TIN NHẮN hay chưa, khác với status phân loại tab
            $lastReadAt = $conv->last_read_at;
            $latestMsgAt = $conv->latest_message_at;

            // Nếu có tin nhắn mới mà thời gian đọc nhỏ hơn thời gian tin nhắn -> UI hiện unread
            if ($latestMsgAt && (!$lastReadAt || $lastReadAt < $latestMsgAt)) {
                $uiStatus = 'unread';
            } else {
                $uiStatus = 'read';
            }

            // 3. Clean up thông tin participants
            $conv->participants->each(function ($p) {
                $p->setVisible(['user_id', 'name', 'avatar_url']);
            });

            // 4. Trả về cấu trúc chuẩn cho Frontend
            return [
                'conversation'   => $conv,
                'latest_message' => $latestMessage,
                'status'         => $uiStatus, // Trạng thái này dùng để hiện dấu thông báo tin nhắn mới
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
        $now = now(); // Lấy thời gian hiện tại
        // Trường hợp private chat: check trước khi tạo
        if (empty($data['is_group']) || !$data['is_group']) {
            $otherUserId = $data['participant_ids'][0];
            $existing = $this->conversationRepo->findPrivateConversation($userId, $otherUserId);

            if ($existing) {
                return $existing->load('participants:user_id,name,avatar_url')
                    ->makeHidden('participants.*.pivot');
            }
        }

        // --- PHẦN THAY ĐỔI ---
        $isGroup = $data['is_group'] ?? false;
        $insertData = [
            'name' => $data['name'] ?? null,
            'is_group' => $isGroup,
            'created_by' => $userId,
        ];

        // Nếu là group, gán avatar mặc định (đường dẫn storage hoặc URL)
        if ($isGroup) {
            $insertData['avatar_url'] = url('storage/avatars/default-group_avatar.png');
            // Hoặc dùng: config('chat.default_group_avatar')
        }
        // ---------------------

        $conversation = $this->conversationRepo->create($insertData);

        if ($conversation->is_group) {
            $conversation->participants()->attach($userId, ['role' => 'admin', 'last_read_at' => $now]);
            // Sử dụng mảng participant_ids trực tiếp
            $conversation->participants()->attach($data['participant_ids'], ['role' => 'member']);
        } else {
            $conversation->participants()->attach([$userId, $data['participant_ids'][0]], ['role' => 'admin']);
        }

        return $conversation->load('participants:user_id,name,avatar_url')
            ->makeHidden('participants.*.pivot');
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
            $currentUser = Auth::user(); // Lấy Object User hiện tại để có thuộc tính 'name'

            $conversation = Conversation::findOrFail($conversationId);
            if (!$conversation->is_group) {
                throw new \Exception("Chỉ có thể xóa thành viên khỏi cuộc trò chuyện nhóm.");
            }

            // Lấy thông tin role (bảng pivot)
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

            if (!$isAdmin && !$isSelfAction) {
                throw new \Exception("Bạn không có quyền xóa thành viên này.");
            }

            // Thực hiện xóa
            DB::table('conversation_participants')
                ->where('conversation_id', $conversationId)
                ->where('user_id', $targetUserId)
                ->delete();

            // Gửi thông báo nếu Admin xóa thành viên
            if ($isAdmin && !$isSelfAction) {
                $targetUser = User::find($targetUserId);
                if ($targetUser) {
                    // SỬA TẠI ĐÂY: Dùng $currentUser->name thay vì $currentUserData->name
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
}
