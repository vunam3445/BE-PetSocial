<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PetController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\SearchController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned the "api" middleware group. Make something great!
|
*/

//Auth Routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);     // POST /api/auth/register
    Route::post('login',    [AuthController::class, 'login']);        // POST /api/auth/login
    Route::post('refresh', [AuthController::class, 'refresh']);

    Route::middleware('auth:api')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);

        // POST /api/auth/logout
    });
});



Route::middleware('auth:api')->group(function () {
    //Profile Routes
    Route::get('/profile/{id}', [ProfileController::class, 'show']);
    Route::post('/users/{id}/updateProfile', [ProfileController::class, 'update']);
    //pet Routes
    // Lấy danh sách pet theo user_id (nếu có)
    Route::get('pets/user/{userId}', [PetController::class, 'getAllPetsByUser']);
    // Tạo mới pet
    Route::post('pets', [PetController::class, 'create']);
    // Lấy chi tiết 1 pet
    Route::get('pets/{petId}', [PetController::class, 'show']);
    // Cập nhật pet
    Route::put('pets/{petId}', [PetController::class, 'update']);
    // Xoá pet
    Route::delete('pets/{petId}', [PetController::class, 'delete']);
    // Post route
    Route::get('posts', [PostController::class, 'getAllPosts']);
    Route::post('posts', [PostController::class, 'create']);
    Route::patch('posts/{id}', [PostController::class, 'update']);
    Route::delete('posts/{id}', [PostController::class, 'delete']);
    // Nested resource
    Route::get('users/{id}/posts', [PostController::class, 'getPostByUserId']);

    Route::get('users/{id}/media/{mediaType}', [ProfileController::class, 'getMedia']);

    // like route
    Route::post('posts/{postId}/likes', [LikeController::class, 'toggle'])
        ->middleware('throttle:like-per-post');

    Route::prefix('posts/{postId}/comments')->group(function () {
        Route::get('/', [CommentController::class, 'index']);    // lấy comment theo post
        Route::post('/', [CommentController::class, 'store']);   // thêm comment
    });
    Route::get('comments/{commentId}/replies', [CommentController::class, 'getReplies']); // lấy reply của comment
    Route::delete('comments/{commentId}', [CommentController::class, 'destroy']); // xoá comment

    // Follow routes
    // Follow / Unfollow
    Route::post('users/{id}/follow', [FollowController::class, 'follow'])
            ->middleware('throttle:follow-unfollow');
    Route::delete('users/{id}/follow', [FollowController::class, 'unfollow'])
            ->middleware('throttle:follow-unfollow'); // unfollow user

    // Danh sách followers và following
    Route::get('users/{id}/followers', [ProfileController::class, 'getFollowers']);
    Route::get('users/{id}/following', [ProfileController::class, 'getFollowing']);


    // Search routes
    Route::get('/search', [SearchController::class, 'search']);
});
