<?php

namespace App\Http\Controllers;

use App\Services\MembershipQuestionService;
use Illuminate\Http\Request;

class MembershipQuestionController extends Controller
{
    protected $membershipService;
    public function __construct(MembershipQuestionService $membershipQuestionService)
    {
        $this->membershipService = $membershipQuestionService;
    }

    public function create(Request $request)
    {
        $data = $request->all();
        $question = $this->membershipService->create($data);
        return response()->json($question);
    }
    public function update(Request $request,string $questionId)
    {
        $data= $request->all();
        $question = $this->membershipService->update($questionId, $data);
        return response()->json($question);
    }
    public function delete(string $questionId)
    {
               $deleted = $this->membershipService->delete($questionId);
        if ($deleted) {
            return response()->json(['message' => 'Question deleted successfully']);
        } else {
            return response()->json(['message' => 'Question not found or delete failed'], 404);
        }
    }
    public function index(Request $request,string $groupId)
    {
        return $this->membershipService->getQuestionOfGroup($groupId);
    }
}
