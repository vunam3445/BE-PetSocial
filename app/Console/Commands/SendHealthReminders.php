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

                // 3. Tính ngày đến hạn
                $dueDate = $lastDate->copy()->addDays($interval);
                
                $today = Carbon::now()->startOfDay();
                $notificationDateBefore2Days = $dueDate->copy()->subDays(2)->startOfDay();
                $actualDueDate = $dueDate->copy()->startOfDay();

                // 4. Kiểm tra: Nếu là hôm nay là TRƯỚC 2 NGÀY hoặc ĐÚNG NGÀY đến hạn
                if ($today->isSameDay($notificationDateBefore2Days) || $today->isSameDay($actualDueDate)) {
                    
                    // Xác định loại thông báo để truyền vào (tùy chọn nếu bạn muốn đổi nội dung tin nhắn)
                    $type = $today->isSameDay($actualDueDate) ? 'đúng hạn' : 'trước 2 ngày';
                    
                    $this->sendNotification($log->pet_id, $category, $dueDate, $type);
                }
            }
        }

        $this->info('Hoàn thành gửi thông báo.');
    }

    protected function sendNotification($petId, $category, $dueDate, $type)
    {
        $pet = Pet::with('user')->find($petId);

        if ($pet && $pet->user) {
            $user = $pet->user;

            // Kiểm tra tránh gửi trùng trong cùng một ngày
            $alreadyNotified = $user->notifications()
                ->whereRaw("data::jsonb->>'pet_id' = ?", [(string)$petId])
                ->whereRaw("data::jsonb->>'category_id' = ?", [(string)$category->category_id])
                ->where('created_at', '>=', now()->startOfDay())
                ->exists();

            if (!$alreadyNotified) {
                // Gửi thông báo
                $user->notify(new HealthReminderNotification($pet, $category, $dueDate));

                $this->line("Đã gửi thông báo ({$type}) cho bé {$pet->name} tới user {$user->name}");
            }
        }
    }
}
