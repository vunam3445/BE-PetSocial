<?php

namespace App\Services;

use App\Repositories\PostRepository\PostRepository;
use App\Repositories\ReportRepository\ReportRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        $reported_by = Auth::id();
        $data['reported_by']= $reported_by;
        return $this->reportRepository->create($data);
    }

    public function update(string $reportId, array $data) 
    {
        return $this->reportRepository->update($reportId, $data);
    }
    public function delete(string $reportId)
{
    return DB::transaction(function () use ($reportId) {
        // 1. Tìm report kèm theo quan hệ để lấy dữ liệu gửi thông báo
        // Sử dụng find của Repository (có findOrFail)
        $report = $this->reportRepository->find($reportId, ['post.group', 'post.author']);

        $post = $report->post;

        if ($post) {
            // 2. Gửi thông báo vi phạm cho tác giả TRƯỚC khi xóa bài
            if ($post->author) {
                // Map lý do từ key sang nhãn tiếng Việt
                
                $post->author->notify(new \App\Notifications\PostDeletedNotification(
                    $post->group, 
                ));
            }

            // 3. Xóa bài viết
            // Lệnh này sẽ kích hoạt PostObserver để xóa file vật lý trong Storage
            $this->postRepository->delete($post->post_id);

            // 4. Xóa TẤT CẢ các báo cáo khác liên quan đến bài viết này
            // Vì bài đã xóa, không cần giữ lại các đơn báo cáo khác của cùng bài này
            \App\Models\Report::where('post_id', $post->post_id)->delete();
        } else {
            // Nếu bài viết đã bị xóa trước đó bởi admin khác, chỉ cần xóa nốt cái report này
            $report->delete();
        }

        return true;
    });
}

    public function getReportOfGroup(string $groupId)
    {
        return $this->reportRepository->getReportOfGroup($groupId);
    }

   
}
