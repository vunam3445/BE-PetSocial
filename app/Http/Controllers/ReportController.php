<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected $reportService;
    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }
    public function create(Request $request)
    {
        $request->validate([
            'reportable_id'   => 'required', // ID của Post/User/Group
            'reportable_type' => 'required|string', // 'post', 'user', hoặc 'group'
            'reason'          => 'required|string|max:255',
            'description'     => 'nullable|string',
        ]);

        $data = $request->all();

        // Map tên ngắn gọn từ Frontend sang Class name đầy đủ của Laravel
        // Ví dụ: 'post' -> 'App\Models\Post'
        $map = [
            'post'  => \App\Models\Post::class,
            'user'  => \App\Models\User::class,
            'group' => \App\Models\Group::class,
        ];

        if (!isset($map[$data['reportable_type']])) {
            return response()->json(['message' => 'Loại báo cáo không hợp lệ'], 400);
        }

        $data['reportable_type'] = $map[$data['reportable_type']];

        $report = $this->reportService->create($data);

        return $report
            ? response()->json($report, 201)
            : response()->json(['message' => 'create report error'], 400);
    }
    public function bulkUpdateStatus(Request $request)
    {
        $request->validate([
            'report_ids' => 'required|array',
            'report_ids.*' => 'exists:reports,report_id',
            'status'     => 'required|in:resolved,dismissed'
        ]);

        $res = $this->reportService->bulkUpdateStatus(
            $request->report_ids,
            $request->status,

        );

        return response()->json($res);
    }

    /**
     * Xóa bài viết và xử lý toàn bộ báo cáo liên quan
     */
    public function bulkDelete(Request $request)
    {
        // Nhận report_ids từ body (do axios gửi {data: {report_ids: [...]}})
        $request->validate([
            'report_ids' => 'required|array',
            'report_ids.*' => 'exists:reports,report_id',
        ]);

        $res = $this->reportService->bulkDelete($request->report_ids);

        return response()->json($res);
    }

    public function getReportOfGroup(string $groupId)
    {
        $data = $this->reportService->getReportOfGroup($groupId);
        return response()->json($data);
    }
}
