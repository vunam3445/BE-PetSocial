<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected $reportService;
    public function __construct(ReportService $reportService)
    {
        $this->reportService= $reportService;
    }

    public function create(Request $request)
    {
        $data= $request->all();
        $report = $this->reportService->create($data);
        if($report){
            return response()->json($report);
        }else{
            return response()->json(['message'=>'create report error'],400);
        }
    }

    public function update(Request $request, string $reportId)
    {
        $data= $request->all();
        $report = $this->reportService->update($reportId, $data);
        return response()->json($report);
    }

    public function delete(string $reportId)
    {
        $report = $this->reportService->delete($reportId);
        return response()->json($report);
    }
    
    public function getReportOfGroup(string $groupId)
    {
        $data= $this->reportService->getReportOfGroup($groupId);
        return response()->json($data);
    }
}
