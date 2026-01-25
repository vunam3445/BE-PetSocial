<?php

namespace App\Services;

use App\Repositories\PostRepository\PostRepository;
use App\Repositories\ReportRepository\ReportRepository;
use App\Models\Report;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Post;

class ReportService
{
    protected $reportRepository;
    protected $postRepository;

    public function __construct(ReportRepository $reportRepository, PostRepository $postRepository)
    {
        $this->reportRepository = $reportRepository;
        $this->postRepository = $postRepository;
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $data['reported_by'] = Auth::id();
            $report = $this->reportRepository->create($data);

            // Nếu đối tượng bị báo cáo là Bài viết (Post)
            if ($data['reportable_type'] === \App\Models\Post::class) {
                $post = \App\Models\Post::find($data['reportable_id']);

                // Nếu bài viết thuộc một nhóm, gửi thông báo gộp cho Admin nhóm đó
                if ($post && $post->group_id) {
                    $this->sendAggregatedReportNotification($post);
                }
            }

            return $report;
        });
    }
    private function sendAggregatedReportNotification($post)
    {
        $group = $post->group;
        if (!$group || !$group->created_by) return;

        $adminId = $group->created_by;

        // 1. Tìm thông báo "Báo cáo gộp" CHƯA ĐỌC của nhóm này
        $existingNotification = DB::table('notifications')
            ->where('notifiable_id', $adminId)
            ->where('notifiable_type', 'App\Models\User')
            ->whereNull('read_at')
            // Sử dụng CAST AS jsonb cho PostgreSQL
            ->whereRaw("CAST(data AS jsonb)->>'type' = ?", ['POST_REPORTED_AGGREGATED'])
            ->whereRaw("CAST(data AS jsonb)->>'group_id' = ?", [$group->group_id])
            ->first();

        // 2. Lấy số lượng BÀI VIẾT KHÁC NHAU đang có báo cáo ở trạng thái 'pending' trong nhóm này
        // Sử dụng Join để lọc bài viết thuộc về Group này
        $distinctPostCount = \App\Models\Report::where('status', 'pending')
            ->where('reportable_type', \App\Models\Post::class)
            ->whereExists(function ($query) use ($group) {
                $query->select(DB::raw(1))
                    ->from('posts')
                    ->whereColumn('posts.post_id', 'reports.reportable_id')
                    ->where('posts.group_id', $group->group_id);
            })
            ->distinct('reportable_id')
            ->count('reportable_id');

        // Nếu con số bằng 0 (ví dụ Admin vừa xử lý hết), ta có thể xóa/ẩn thông báo này đi
        if ($distinctPostCount === 0 && $existingNotification) {
            DB::table('notifications')->where('id', $existingNotification->id)->delete();
            // Gửi socket báo xóa thông báo cho FE (nếu cần)
            return;
        }

        $message = "Có {$distinctPostCount} bài viết trong nhóm {$group->name} đang bị báo cáo vi phạm.";

        if ($existingNotification) {
            // 3. Cập nhật thông báo cũ
            $payload = json_decode($existingNotification->data, true);
            $payload['report_count'] = $distinctPostCount;
            $payload['message'] = $message;
            $payload['updated_at'] = now()->toDateTimeString();

            DB::table('notifications')
                ->where('id', $existingNotification->id)
                ->update([
                    'data' => json_encode($payload),
                    'updated_at' => now()
                ]);

            $notificationId = $existingNotification->id;
        } else {
            // 4. Tạo thông báo mới (sử dụng UUID làm ID)
            $notificationId = (string) \Illuminate\Support\Str::uuid();
            $payload = [
                'type' => 'POST_REPORTED_AGGREGATED',
                'group_id' => $group->group_id,
                'group_name' => $group->name,
                'report_count' => $distinctPostCount,
                'message' => $message,
                'avatar_url' => $group->avatar_url,
                'target_url' => "/groups/{$group->group_id}",
                'created_at' => now()->toDateTimeString(),
            ];

            DB::table('notifications')->insert([
                'id' => $notificationId,
                'type' => 'App\Notifications\PostReportedNotification',
                'notifiable_type' => 'App\Models\User',
                'notifiable_id' => $adminId,
                'data' => json_encode($payload),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 5. Gửi Socket Real-time
        \App\Jobs\SendSocketNotificationJob::dispatch($adminId, [
            'id' => $notificationId,
            'data' => $payload,
            'read_at' => null,
            'created_at' => now()->toDateTimeString(),
        ]);
    }
    public function bulkUpdateStatus(array $reportIds, string $status)
    {
        $adminId = Auth::id();
        return DB::transaction(function () use ($reportIds, $status, $adminId) {
            // Cập nhật trạng thái hàng loạt
            Report::whereIn('report_id', $reportIds)->update([
                'status' => $status,
                'handled_by' => $adminId,
                'handled_at' => now()
            ]);

            //Lấy 1 báo cáo để tìm ra thông tin Post và Group nhằm cập nhật lại Noti gộp
            $firstReport = Report::where('report_id', $reportIds[0])->first();
            if ($firstReport && $firstReport->reportable_type === Post::class) {
                $post = Post::find($firstReport->reportable_id);
                if ($post) {
                    // Gọi hàm cập nhật lại thông báo gộp (để nhảy số hoặc xóa noti trên FE)
                    $this->sendAggregatedReportNotification($post);
                }
            }

            return ['success' => true];
        });
    }

    /**
     * Xử lý Xóa bài viết (Reject/Delete)
     */
    public function bulkDelete(array $reportIds)
    {
         $adminId = Auth::id();
        return DB::transaction(function () use ($reportIds, $adminId) {
            $reports = Report::whereIn('report_id', $reportIds)->get();
            if ($reports->isEmpty()) return false;

            $firstReport = $reports->first();

            // Nếu là báo cáo Bài viết
            if ($firstReport->reportable_type === Post::class) {
                $postId = $firstReport->reportable_id;
                $post = Post::find($postId);

                if ($post) {
                    $this->postRepository->delete($post->post_id);
                }

                // 3. Chuyển TẤT CẢ báo cáo (bao gồm cả những cái không nằm trong mảng gửi lên) 
                // của bài viết này thành 'resolved'
                Report::where('reportable_id', $postId)
                    ->where('reportable_type', Post::class)
                    ->update([
                        'status' => 'resolved',
                        'handled_by' => $adminId,
                        'handled_at' => now()
                    ]);

                // 4. Cập nhật lại thông báo gộp (số lượng sẽ về 0 và biến mất)
                // if ($post) $this->sendAggregatedReportNotification($post);
            }

            return ['success' => true];
        });
    }
    public function getReportOfGroup(string $groupId)
    {
        // Repository cần cập nhật query để join qua bảng posts dựa trên reportable_id
        return $this->reportRepository->getReportOfGroup($groupId);
    }
}
