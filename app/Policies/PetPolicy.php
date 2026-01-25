<?php

namespace App\Policies;

use App\Models\Pet;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PetPolicy
{
    use HandlesAuthorization;

    /**
     * Xác định xem người dùng có quyền cập nhật thông tin thú cưng không.
     */
    public function update(User $user, Pet $pet): bool
    {
        // Kiểm tra xem user_id của người dùng có trùng với user_id của chủ pet không
        return $user->user_id === $pet->user_id;
    }

    /**
     * Xác định xem người dùng có quyền xóa thú cưng không.
     */
    public function delete(User $user, Pet $pet): bool
    {
        // Thông thường quyền xóa cũng chỉ dành cho chủ sở hữu
        return $user->user_id === $pet->user_id;
    }
}