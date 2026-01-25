<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\PetHealthLog;
use App\Models\Pet;
use App\Models\User;
use App\Notifications\HealthReminderNotification;

class SendHealthReminders extends Command
{
    protected $signature = 'app:send-health-reminders';
    protected $description = 'Kiểm tra và gửi thông báo nhắc nhở sức khỏe thú cưng trước 2 ngày';

    public function handle()
    {
        $this->info('Đang kiểm tra lịch nhắc nhở sức khỏe...');

        // 1. Lấy tất cả các category có thiết lập định kỳ
        $categories = DB::table('health_categories')
            ->whereNotNull('interval_days')
            ->where('interval_days', '>', 0)
            ->whereIn('category_type', ['treatment', 'schedule'])
            ->get();

        foreach ($categories as $category) {
            // 2. Tìm bản ghi gần nhất của từng thú cưng trong category này
            $latestLogs = PetHealthLog::where('category_id', $category->category_id)
                ->select('pet_id', DB::raw('MAX(recorded_at) as last_recorded'))
                ->groupBy('pet_id')
                ->get();

            foreach ($latestLogs as $log) {
                $lastDate = Carbon::parse($log->last_recorded);
                $interval = $category->interval_days;

                // 3. Tính ngày đến hạn và ngày cần gửi thông báo
                $dueDate = $lastDate->copy()->addDays($interval);
                $notificationDate = $dueDate->copy()->subDays(2); // Thông báo trước 2 ngày

                // 4. Kiểm tra nếu hôm nay là ngày cần thông báo
                if (Carbon::now()->isSameDay($notificationDate)) {
                    $this->sendNotification($log->pet_id, $category, $dueDate);
                }
            }
        }

        $this->info('Hoàn thành gửi thông báo.');
    }

    protected function sendNotification($petId, $category, $dueDate)
    {
        // 1. Lấy thông tin Pet kèm theo chủ sở hữu (User)
        $pet = Pet::with('user')->find($petId);

        if ($pet && $pet->user) {
            $user = $pet->user;

            // 2. Kiểm tra tránh gửi trùng (giới hạn 1 thông báo/loại/ngày)
            // 2. Kiểm tra tránh gửi trùng (ép kiểu text sang jsonb để truy vấn)
            $alreadyNotified = $user->notifications()
                ->whereRaw("data::jsonb->>'pet_id' = ?", [$petId])
                ->whereRaw("data::jsonb->>'category_id' = ?", [$category->category_id])
                ->where('created_at', '>=', now()->startOfDay())
                ->exists();

            if (!$alreadyNotified) {
                // 3. Gửi thông báo bằng class Notification đã cấu hình
                $user->notify(new HealthReminderNotification($pet, $category, $dueDate));

                $this->line("Đã gửi thông báo cho bé {$pet->name} tới user {$user->name}");
            }
        }
    }
}
