<?php

namespace App\Policies;

use App\Models\HealthCategory;
use App\Models\User;
use App\Models\Pet;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response; // Import thêm class này
use Illuminate\Support\Facades\Log;
class PetHealthPolicy
{
    use HandlesAuthorization;

    /**
     * Chỉ chủ sở hữu pet mới có quyền tạo Category cho pet đó
     */
    public function create(User $user, Pet $pet): Response
    {   
        return $user->user_id === $pet->user_id
            ? Response::allow()
            : Response::deny('Bạn không phải là chủ sở hữu của thú cưng này để thực hiện thao tác.');
    }

    /**
     * Chỉ chủ sở hữu pet mới có quyền sửa Category
     */
    public function update(User $user, HealthCategory $category): Response
    {
        return $user->user_id === $category->pet->user_id
            ? Response::allow()
            : Response::deny('Bạn không có quyền chỉnh sửa danh mục sức khỏe này.');
    }

    /**
     * Chỉ chủ sở hữu pet mới có quyền xóa Category
     */
    public function delete(User $user, HealthCategory $category): Response
    {
        return $user->user_id === $category->pet->user_id
            ? Response::allow()
            : Response::deny('Bạn không có quyền xóa danh mục sức khỏe này.');
    }
}