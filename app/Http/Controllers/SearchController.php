<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SearchService;

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
            'type' => 'required|string|in:user,post,pet',
            'keyword' => 'required|string',
        ]);

        $type = $request->input('type');
        $keyword = $request->input('keyword');
        $limit = $request->input('limit', 10);

        $results = $this->searchService->search($type, $keyword, $limit);

        return response()->json($results);
    }
}
