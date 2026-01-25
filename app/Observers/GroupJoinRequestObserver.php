<?php

namespace App\Observers;

// Lưu ý: Import đúng Model và Notification
use App\Models\GroupJoinRequest as GroupJoinRequestModel; 
use App\Notifications\GroupJoinRequestNotification;
use App\Models\User;

class GroupJoinRequestObserver
{
    /**
     * Handle the GroupJoinRequest "created" event.
     */
   public function created(GroupJoinRequestModel $request): void
{
    $group = $request->group;
    if ($group && $group->admin_id) {
        $admin = User::find($group->admin_id);
        if ($admin) {
            // Chỉ cần dòng này là đủ, notify() sẽ tự gọi toArray() và dispatch Job
            $admin->notify(new GroupJoinRequestNotification($request));
        }
    }
}
}