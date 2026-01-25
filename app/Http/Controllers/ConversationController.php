<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ConversationService;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\ConversationResource;
use Illuminate\Support\Facades\Validator;
use Exception;
use App\Models\Conversation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests; 
class ConversationController extends Controller
{
    use AuthorizesRequests;

    protected $conversationService;

    public function __construct(ConversationService $conversationService)
    {
        $this->conversationService = $conversationService;
    }

    // Lấy danh sách cuộc trò chuyện của user
    public function index(Request $request)
    {
        $userId = Auth::id();
        $filterStatus = $request->query('status');

        $conversations = $this->conversationService->getUserConversations($userId, $filterStatus);

        return response()->json($conversations);
    }

    // Tạo cuộc trò chuyện mới
    public function store(Request $request)
    {
        $request->validate([
            'participant_ids' => 'array',
            'is_group' => 'boolean',
            'name' => 'string|max:255'
        ]);

        $userId = Auth::id();
        $conversation = $this->conversationService->createConversation([
            'participant_ids' => $request->participant_ids,
            'is_group' => $request->is_group,
            'name' => $request->name,
        ]);

        return new ConversationResource($conversation);
    }



    // Xóa cuộc trò chuyện
    public function destroy(string $id)
    {
        $this->conversationService->deleteConversation($id);

        return response()->json(['message' => 'Conversation deleted']);
    }
    public function update(string $conversationId)
    {
        $userId = Auth::id();
        $this->conversationService->markAsRead($userId, $conversationId);

        return response()->json(['message' => 'Marked as read']);
    }

    public function recent(Request $request)
    {
        $limit = $request->query('limit', 10);

        $conversations = $this->conversationService->getRecentConversations($limit);

        return response()->json($conversations);
    }

    public function getMemberOfConversation(string $conversationId)
    {
        $members = $this->conversationService->getMemberOfConversation($conversationId);
        return response()->json($members);
    }

    public function getMembersToInvite(string $conversationId)
    {
        $member = $this->conversationService->getMembersToInvite($conversationId);
        return response()->json($member);
    }
    public function inviteMembers(Request $request, $conversationId) // Lấy ID từ URL
    {
        // 1. Chỉ validate userIds từ Body
        $validated = $request->validate([
            'userIds' => 'required|array|min:1',
            'userIds.*' => 'exists:users,user_id',
        ]);

        // 2. Sử dụng $conversationId từ URL và $userIds từ Body
        $userIds = $validated['userIds'];

        try {

            $res = $this->conversationService->inviteMembers($conversationId, $userIds);

            return response()->json([
                'status' => 'success',
                'message' => 'Đã gửi lời mời',
                'data' => $res
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }
    public function handleInvitation(Request $request)
    {
        // 1. Validate dữ liệu đầu vào
        $validated = $request->validate([
            'notificationId' => 'required|string',
            'status'         => 'required|in:accepted,rejected',
        ]);

        try {
            // 2. Gọi Service để xử lý logic nghiệp vụ
            // Giả sử bạn đã inject ConversationService vào constructor hoặc dùng resolve()
            $res = $this->conversationService->handleInvitation(
                $validated['notificationId'],
                $validated['status']
            );

            // 3. Trả về kết quả thành công
            return response()->json([
                'status'  => 'success',
                'message' => $res['message'],
                'data'    => $res
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Không tìm thấy thông báo hoặc bạn không có quyền xử lý.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteMember(Request $request, $conversationId, $targetId)
    {
        try {
            // Gọi Service với 2 tham số lấy từ URL
            $result = $this->conversationService->deleteMember($conversationId, $targetId);

            return response()->json($result, 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 403);
        }
    }
    public function updateConversation(Request $request, $conversationId)
    {
        // 1. Validate dữ liệu
        $validator = Validator::make($request->all(), [
            'name'   => 'nullable|string|max:255',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // Tối đa 5MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Dữ liệu không hợp lệ',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            // Chuẩn bị dữ liệu gửi qua Service
            $data = [
                'name'   => $request->input('name'),
                'avatar' => $request->file('avatar'), // Truyền instance của UploadedFile
            ];

            $result = $this->conversationService->updateConversation($conversationId, $data);

            if (!$result) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Không có thông tin nào được cập nhật'
                ], 400);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Cập nhật cuộc trò chuyện thành công',
                'data'    => $result
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Đã xảy ra lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    public function changeRole(Request $request, string $conversationId, string $targetId)
    {
        try {
            $res = $this->conversationService->changeRole($conversationId, $targetId);
            return response()->json($res);
        } catch (\Exception $e) {
            // Trả về lỗi nếu có vấn đề (ví dụ: không tìm thấy user hoặc không đủ quyền)
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function delete(string $conversationId)
    {
        // 1. Tìm conversation
        $conversation = Conversation::findOrFail($conversationId);

        // 2. Thực hiện kiểm tra quyền từ Policy
        // Nó sẽ gọi hàm delete() trong ConversationPolicy
        $this->authorize('delete', $conversation);

        // 3. Nếu vượt qua (là admin), tiến hành xóa thông qua Service
        $res = $this->conversationService->delete($conversationId);

        return response()->json($res);
    }
}
