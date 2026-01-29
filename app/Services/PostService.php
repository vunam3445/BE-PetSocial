<?php

namespace App\Services;

use App\Models\Group;
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
            $currentUserId = Auth::id();
            if ($currentUserId !== $data['author_id']) {
                throw new AuthorizationException('Bạn không có quyền đăng bài này.');
            }
            $data['status'] = 'approved';
            if (!empty($data['group_id'])) {
                $group = Group::find($data['group_id']);
                if ($group && $group->require_post_approval) {
                    $data['status'] = 'pending';
                }
            }
            // 1. Tạo post
            $post = $this->postRepository->create([
                'author_id'      => $data['author_id'],
                'caption'        => $data['caption'] ?? null,
                'visibility'     => $data['visibility'] ?? 'public',
                'shared_post_id' => $data['shared_post_id'] ?? null,
                'group_id'       => $data['group_id'] ?? null,
                'pet_id'        => $data['pet_id'] ?? null,
                'status'         => $data['status']
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
            $postTags = $post->tags()->pluck('name')->toArray();

            \App\Models\UserInteraction::create([
                'interaction_id' => Str::uuid(),
                'user_id'        => $post->author_id,
                'post_id'        => $post->post_id,
                'action_type'    => 'create_post',
                'weight'         => 10,
                'metadata'       => [
                    'tags'     => $postTags,
                    'pet_type' => $post->pet?->type,
                    'caption'  => Str::limit($post->caption, 100)
                ],
            ]);
            if ($post->status === 'pending' && !empty($post->group_id)) {
                // Load quan hệ group để lấy admin_id và tên group
                $post->load('group');
                $this->sendPendingPostNotification($post);
            }
            return $post
                ->load([
                    'media',
                    'author:user_id,name,avatar_url',
                    'tags',
                    'sharedPost.media',
                    'sharedPost.author:user_id,name,avatar_url',
                    'sharedPost.tags',
                    'pet:pet_id,name,avatar_url',
                    'group:group_id,name,avatar_url'
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
        $ownerId = Auth::id();
        $query = $this->postRepository
            ->getQuery()
            ->with(array_merge($relations, ['sharedPost.media', 'sharedPost.author:user_id,name,avatar_url', 'sharedPost.tags','sharedPost.pet:pet_id,name,avatar_url']));
        $query->whereNull('group_id')
            ->whereNull('pet_id');
        if (!empty($withCount)) {
            $query->withCount($withCount);
        }

        // thêm flag is_liked
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
            ->selectRaw('*, (author_id = ?) as is_owner', [$ownerId])
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
            $status     = $data['status'] ?? 'approved';
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
                'status'     => $status
            ]);

            $post->load([
                'media',
                'author:user_id,name,avatar_url',
                'tags',
                'sharedPost.media',
                'sharedPost.author:user_id,name,avatar_url',
                'sharedPost.tags',
                'group:group_id,name',
                'pet:pet_id,name,avatar_url'
            ])->loadCount(['likes', 'comments']);
            $post->setAttribute('is_owner', true);
            return $post;
        });
    }
    public function deletePost(string $id)
    {
        $post = $this->postRepository->find($id, ['media']);
        if (!$post) {
            throw new NotFoundHttpException("Post not found");
        }

        $currentUserId = Auth::id();

        //  Chỉ owner mới được xoá
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
        int $limit = 20
    ) {
        return $this->postRepository->getAllPosts($relations, $withCount, $limit);
    }
    public function getPostsOfGroup(string $groupId, array $relations = [], array $withCount = [], int $limit = 20)
    {
        return $this->postRepository->getPostsOfGroup($groupId, $relations, $withCount, $limit);
    }
    public function getPostsPendingOfGroup(string $groupId, array $relations = [], array $withCount = [], int $limit = 20)
    {
        return $this->postRepository->getPostsPendingOfGroup($groupId, $relations, $withCount, $limit);
    }
    public function resetSeed()
    {
        return $this->postRepository->resetSeed();
    }

    public function sendPendingPostNotification($post)
    {
        $group = $post->group;
        if (!$group) return;

        // Lấy admin (chủ nhóm)
        $adminId = $group->created_by;

        // 1. Tìm thông báo cũ chưa đọc theo chuẩn JSON của bảng notifications
        // Lưu ý: data là kiểu TEXT/JSON nên cần trỏ vào đúng key
        $existingNotification = DB::table('notifications')
            ->where('notifiable_id', $adminId)
            ->where('notifiable_type', 'App\Models\User')
            ->whereNull('read_at')
            // Ép kiểu data sang jsonb để sử dụng toán tử ->>
            ->whereRaw('CAST(data AS jsonb)->>\'type\' = ?', ['POST_PENDING_AGGREGATED'])
            ->whereRaw('CAST(data AS jsonb)->>\'group_id\' = ?', [$group->group_id])
            ->first();
        if ($existingNotification) {
            // 2. Cập nhật cộng dồn dữ liệu
            $payload = json_decode($existingNotification->data, true);
            $count = ($payload['pending_count'] ?? 1) + 1;

            $payload['pending_count'] = $count;
            $payload['message'] = "Có {$count} bài viết mới đang chờ duyệt trong nhóm {$group->name}.";
            $payload['updated_at'] = now()->toDateTimeString();

            DB::table('notifications')
                ->where('id', $existingNotification->id)
                ->update([
                    'data' => json_encode($payload),
                    'updated_at' => now()
                ]);

            $notificationId = $existingNotification->id;
        } else {
            // 3. Tạo mới hoàn toàn bản ghi sử dụng UUID
            $notificationId = (string) \Illuminate\Support\Str::uuid();
            $payload = [
                'type'          => 'POST_PENDING_AGGREGATED',
                'group_id'      => $group->group_id,
                'group_name'    => $group->name,
                'pending_count' => 1,
                'message'       => "Có 1 bài viết mới đang chờ duyệt trong nhóm {$group->name}.",
                'avatar_url'    => $group->avatar_url,
                'target_url'    => "/groups/{$group->group_id}",
                'created_at'    => now()->toDateTimeString(),
            ];

            DB::table('notifications')->insert([
                'id'              => $notificationId,
                'type'            => 'App\Notifications\PendingPostNotification',
                'notifiable_type' => 'App\Models\User',
                'notifiable_id'   => $adminId,
                'data'            => json_encode($payload),
                'read_at'         => null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        // 4. Payload gửi qua Socket (để React render ngay lập tức)
        $fullPayload = [
            'id'            => $notificationId,
            'type'          => 'App\Notifications\PendingPostNotification',
            'notifiable_id' => $adminId,
            'data'          => $payload,
            'read_at'       => null,
            'created_at'    => now()->toDateTimeString(),
        ];

        \App\Jobs\SendSocketNotificationJob::dispatch($adminId, $fullPayload);
    }
}
