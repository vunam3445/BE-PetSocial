<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SearchService;
use Symfony\Component\CssSelector\Parser\Reader;

class SearchController extends Controller
{
    protected SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    public function search(Request $request)
    {
        $request->validate([
            'type' => 'required|string|in:user,post,pet,group',
            'keyword' => 'required|string',
        ]);

        $type = $request->input('type');
        $keyword = $request->input('keyword');
        $limit = $request->input('limit', 10);

        $results = $this->searchService->search($type, $keyword, $limit);

        return response()->json($results);
    }

    public function searchRequestJoinGroup(Request $request, string $groupId)
    {   
        $keyword = $request->query('keyword','');
        $res = $this->searchService->searchRequestJoinGroup($groupId, $keyword);
        return response()->json($res);
    }
    public function searchUsersOfGroup(Request $request, string $groupId)
    {
        $keyword = $request->query('keyword', '');
        $res = $this->searchService->searchUsersOfGroup($groupId,$keyword);
        return response()->json($res);
    }

    public function searchConversation(Request $request)
    {
        $keyword = $request->query('keyword','');
        $res = $this->searchService->searchConversation($keyword);
        return response()->json($res);
    }
}
