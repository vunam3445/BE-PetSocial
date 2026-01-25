<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Kiểm tra xem user có quyền cập nhật thông tin/mật khẩu của tài khoản này không.
     */
    public function update(User $user, User $model): bool
    {
        // Chỉ cho phép nếu ID của người đang login trùng với ID của tài khoản mục tiêu
        return $user->user_id === $model->user_id;
    }

    /**
     * Có thể thêm các quyền khác như xóa tài khoản, xem thông tin ẩn...
     */
    public function delete(User $user, User $model): bool
    {
        return $user->user_id === $model->user_id;
    }
}