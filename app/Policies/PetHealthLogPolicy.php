<?php

namespace App\Policies;

use App\Models\PetHealthLog;
use App\Models\User;
use App\Models\Pet;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class PetHealthLogPolicy
{
    use HandlesAuthorization;

    /**
     * Chỉ chủ sở hữu thú cưng mới có quyền tạo bản ghi log mới.
     * Lưu ý: Ta truyền thêm instance $pet để kiểm tra quyền sở hữu.
     */
    public function create(User $user, Pet $pet): Response
    {
        return $user->user_id === $pet->user_id
            ? Response::allow()
            : Response::deny('Bạn không có quyền thêm nhật ký cho thú cưng của người khác.');
    }

    /**
     * Xác định xem người dùng có quyền cập nhật bản ghi log không.
     */
    public function update(User $user, PetHealthLog $log): Response
    {
        // Truy ngược từ Log -> Pet -> user_id
        return $user->user_id === $log->pet->user_id
            ? Response::allow()
            : Response::deny('Bạn không có quyền chỉnh sửa nhật ký sức khỏe này.');
    }

    /**
     * Xác định xem người dùng có quyền xóa bản ghi log không.
     */
    public function delete(User $user, PetHealthLog $log): Response
    {
        return $user->user_id === $log->pet->user_id
            ? Response::allow()
            : Response::deny('Bạn không có quyền xóa nhật ký sức khỏe này.');
    }
}