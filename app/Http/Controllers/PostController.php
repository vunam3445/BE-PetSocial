<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreatePostRequest;
use App\Services\PostService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PostController extends Controller
{
    protected $postService;

    public function __construct(PostService $postService)
    {
        $this->postService = $postService;
    }

    public function create(CreatePostRequest $request)
    {
        $data = $request->validated();
        $res = $this->postService->createPost($data);

        if ($res) {
            return response()->json($res, 201);
        }

        return response()->json(['message' => 'Failed to create post'], 400);
    }

    public function getPostByUserId(string $id)
    {
        $posts = $this->postService->getByField('author_id', $id, ['media', 'author:user_id,name,avatar_url'], ['likes'], 10);
        return response()->json([
            'posts' => $posts
        ]);
    }

    public function update($id, Request $request)
    {
        // Lấy metadata từ JSON
        $mediaMeta = json_decode($request->input('media', '[]'), true) ?? [];

        // Lấy file upload
        $files = $request->file('files', []);

        // Merge metadata + files
        $media = [];
        foreach ($mediaMeta as $index => $item) {
            $media[$index] = $item;

            if (!empty($files[$index])) {
                $media[$index]['file'] = $files[$index];
            }
        }

        $data = [
            'caption'    => $request->input('caption'),
            'visibility' => $request->input('visibility'),
            'media'      => $media,
        ];

        $res = $this->postService->updatePost($id, $data);

        if ($res) {
            return response()->json($res);
        }

        return response()->json(['message' => 'Failed to update post'], 400);
    }

    public function delete($id)
    {
        $res = $this->postService->deletePost($id);

        if ($res) {
            return response()->json(['message' => 'Post deleted successfully']);
        }

        return response()->json(['message' => 'Failed to delete post'], 400);
    }
    public function getAllPosts()
    {

        $posts = $this->postService->getAllPosts(['media', 'author:user_id,name,avatar_url'], ['likes'], 10);

        return response()->json([
            'posts' => $posts
        ]);
    }
}
