<?php

namespace App\Services;

use App\Repositories\PostRepository\PostInterface;
use App\Repositories\TagRepository\TagInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Access\AuthorizationException;

class PostService
{
    protected $postRepository;
    protected $tagRepository;

    public function __construct(PostInterface $postRepository, TagInterface $tagRepository)
    {
        $this->postRepository = $postRepository;
        $this->tagRepository = $tagRepository;
    }

    public function createPost(array $data)
    {
        return DB::transaction(function () use ($data) {
            // ✅ 0. Kiểm tra chính chủ
            $currentUserId = Auth::id();
            if ($currentUserId !== $data['author_id']) {
                throw new AuthorizationException('Bạn không có quyền đăng bài với user_id này.');
            }
            
            // 1. Tạo post
            $post = $this->postRepository->create([
                'author_id'      => $data['author_id'],
                'caption'        => $data['caption'] ?? null,
                'visibility'     => $data['visibility'] ?? 'public',
                'shared_post_id' => $data['shared_post_id'] ?? null,
                'group_id'       => $data['group_id'] ?? null,
            ]);

            // 2. Xử lý tag
            $caption = $data['caption'] ?? '';
            preg_match_all('/#(\w+)/u', $caption, $matches);

            $tags = array_unique(array_map(fn($tag) => Str::lower(trim($tag)), $matches[1]));

            if (!empty($tags)) {
                $tagIds = $this->tagRepository->upsertAndGetIds($tags);
                $post->tags()->syncWithoutDetaching($tagIds);
            }

            // 3. Xử lý media
            if (!empty($data['media'])) {
                foreach ($data['media'] as $index => $media) {
                    if (!isset($media['file']) || !$media['file']->isValid()) {
                        continue;
                    }

                    $file = $media['file'];
                    $mediaType = $media['media_type'] ?? null;
                    $order = $media['order'] ?? $index;

                    $ext = $file->getClientOriginalExtension();
                    $fileName = Str::uuid() . '.' . $ext;

                    $path = match ($mediaType) {
                        'image' => $file->storeAs('uploads/posts/images', $fileName, 'public'),
                        'video' => $file->storeAs('uploads/posts/videos', $fileName, 'public'),
                        default => null
                    };

                    if ($path) {
                        $mediaUrl = url('storage/' . $path);

                        $post->media()->create([
                            'media_url'  => $mediaUrl,
                            'media_type' => $mediaType,
                            'order'      => $order,
                        ]);
                    }
                }
            }

            return $post
                ->load([
                    'media',
                    'author:user_id,name,avatar_url',
                    'tags',
                    'sharedPost.media',
                    'sharedPost.author:user_id,name,avatar_url',
                    'sharedPost.tags'
                ])
                ->loadCount(['likes', 'comments']);
        });
    }



    public function getByField(
        string $field,
        $value,
        array $relations = [],
        array $withCount = [],
        int $limit = 20
    ) {
        $query = $this->postRepository
            ->getQuery()
            ->with(array_merge($relations, ['sharedPost.media', 'sharedPost.author:user_id,name,avatar_url', 'sharedPost.tags']));

        if (!empty($withCount)) {
            $query->withCount($withCount);
        }

        // ✅ thêm flag is_liked
        $userId = Auth::id();
        if ($userId) {
            $query->withCount([
                'comments',
                'likes as is_liked' => function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                }
            ]);
        }

        return $query
            ->where($field, $value)
            ->orderBy('updated_at', 'desc')
            ->paginate($limit);
    }


    public function updatePost(string $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $post = $this->postRepository->find($id, ['media']);
            if (!$post) {
                throw new NotFoundHttpException("Post not found");
            }

            $caption    = $data['caption'] ?? $post->caption;
            $visibility = $data['visibility'] ?? $post->visibility;
            $newMedia   = $data['media'] ?? [];

            $existingMedia = $post->media; // collection
            $mediaToKeep   = [];
            $mediaToAdd    = [];

            foreach ($newMedia as $index => $mediaItem) {
                if (isset($mediaItem['file']) && $mediaItem['file'] instanceof \Illuminate\Http\UploadedFile && $mediaItem['file']->isValid()) {
                    // File mới
                    $file      = $mediaItem['file'];
                    $mediaType = $mediaItem['media_type'] ?? 'image';
                    $order     = $mediaItem['order'] ?? $index;

                    $ext      = $file->getClientOriginalExtension();
                    $fileName = Str::uuid() . '.' . $ext;

                    $path = match ($mediaType) {
                        'image' => $file->storeAs('uploads/posts/images', $fileName, 'public'),
                        'video' => $file->storeAs('uploads/posts/videos', $fileName, 'public'),
                        default => $file->storeAs('uploads/posts/others', $fileName, 'public'),
                    };

                    $mediaToAdd[] = [
                        'post_id'    => $post->id,
                        'media_url'  => url('storage/' . $path),
                        'media_type' => $mediaType,
                        'order'      => $order,
                    ];
                } else {
                    // Media cũ: giữ lại theo id hoặc media_url
                    if (!empty($mediaItem['id'])) {
                        $exists = $existingMedia->where('id', $mediaItem['id'])->first();
                        if ($exists) {
                            $mediaToKeep[] = $exists->id;
                        }
                    } elseif (!empty($mediaItem['media_url'])) { // ✅ sửa lại từ 'url' → 'media_url'
                        $exists = $existingMedia->where('media_url', $mediaItem['media_url'])->first();
                        if ($exists) {
                            $mediaToKeep[] = $exists->id;
                        }
                    }
                }
            }


            // Xóa media không còn trong request
            foreach ($existingMedia as $media) {
                $found = false;

                foreach ($newMedia as $mediaItem) {
                    if (
                        (!empty($mediaItem['id']) && $mediaItem['id'] == $media->id) ||
                        (!empty($mediaItem['media_url']) && $mediaItem['media_url'] == $media->media_url)
                    ) {
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $path = str_replace(url('storage') . '/', '', $media->media_url);
                    if (Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);
                    }
                    $media->delete();
                }
            }

            // Thêm media mới
            if (!empty($mediaToAdd)) {
                $post->media()->createMany($mediaToAdd);
            }

            // Update post
            $post->update([
                'caption'    => $caption,
                'visibility' => $visibility,
            ]);

            return $post->load([
                'media',
                'author:user_id,name,avatar_url',
                'tags',
                'sharedPost.media',
                'sharedPost.author:user_id,name,avatar_url',
                'sharedPost.tags'
            ])->loadCount(['likes', 'comments']);
        });
    }
    public function deletePost(string $id)
    {
        $post = $this->postRepository->find($id, ['media']);
        if (!$post) {
            throw new NotFoundHttpException("Post not found");
        }

        $currentUserId = Auth::id();

        // ✅ Chỉ owner mới được xoá
        if ((string)$post->author_id !== (string)$currentUserId) {
            throw new AccessDeniedHttpException("You do not have permission to delete this post");
        }

        // Xoá media khỏi storage
        foreach ($post->media as $media) {
            $path = str_replace(url('storage') . '/', '', $media->media_url);
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        // Xoá post
        return $this->postRepository->delete($id);
    }

    public function getAllPosts(
        array $relations = [],
        array $withCount = [],
        int $limit = 15
    ) {
        return $this->postRepository->getAllPosts($relations, $withCount, $limit);
    }
}
