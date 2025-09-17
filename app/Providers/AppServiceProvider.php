<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use App\Repositories\AuthRepository\AuthInterface;
use App\Repositories\AuthRepository\AuthRepository;
use App\Repositories\ProfileRepository\ProfileInterface;
use App\Repositories\ProfileRepository\ProfileRepository;
use App\Repositories\PetRepository\PetInterface;
use App\Repositories\PetRepository\PetRepository;
use App\Repositories\PostRepository\PostInterface;
use App\Repositories\PostRepository\PostRepository;
use App\Repositories\TagRepository\TagInterface;
use App\Repositories\TagRepository\TagRepository;
use App\Repositories\LikeRepository\LikeInterface;
use App\Repositories\LikeRepository\LikeRepository;
use App\Repositories\CommentRepository\CommentInterface;
use App\Repositories\CommentRepository\CommentRepository;
use App\Repositories\FollowRepository\FollowInterface;
use App\Repositories\FollowRepository\FollowRepository;
use App\Repositories\SearchRepository\SearchInterface;
use App\Repositories\SearchRepository\SearchRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AuthInterface::class, AuthRepository::class);
        $this->app->bind(ProfileInterface::class, ProfileRepository::class);
        $this->app->bind(PetInterface::class, PetRepository::class);
        $this->app->bind(PostInterface::class, PostRepository::class);
        $this->app->bind(TagInterface::class, TagRepository::class);
        $this->app->bind(LikeInterface::class, LikeRepository::class);
        $this->app->bind(CommentInterface::class, CommentRepository::class);
        $this->app->bind(FollowInterface::class, FollowRepository::class);
        $this->app->bind(SearchInterface::class, SearchRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // cấu hình chống spam like
        $this->configureRateLimiting();

        // routes có thể được đăng ký trong RouteServiceProvider hoặc trong routes/web.php, routes/api.php, v.v.
        // Nếu cần đăng ký routes ở đây, hãy sử dụng Route::middleware() hoặc Route::group() trực tiếp.
    }
    protected function configureRateLimiting()
    {
        RateLimiter::for('like-per-post', function ($request) {
            $userId = $request->user()?->id ?? $request->ip();
            $postId = $request->route('postId');

            return [
                Limit::perMinute(5)->by($userId . '|' . $postId),
            ];
        });
        RateLimiter::for('follow-unfollow', function ($request) {
            $userId = $request->user()?->id ?? $request->ip();
            $targetId = $request->route('id'); // user mà mình follow/unfollow

            return [
                // Giới hạn 5 lần mỗi phút cho cùng 1 cặp (người theo dõi -> người được theo dõi)
                Limit::perMinute(5)->by($userId . '|' . $targetId),
            ];
        });
    }
}
