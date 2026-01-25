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
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\HealthCategoryController;
use App\Http\Controllers\PetHealthLogController;
use App\Http\Controllers\PetFollowController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GroupMemberController;
use App\Http\Controllers\MembershipQuestionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
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
    Route::post('confirm-register', [AuthController::class, 'confirmRegister']);
    Route::post('sendOtp',[AuthController::class,'sendOtp']);
    Route::post('confirmOtp',[AuthController::class,'confirmOtp']);
    Route::post('resetPassword',[AuthController::class,'resetPassword']);
    Route::middleware('auth:api')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);

        // POST /api/auth/logout
    });
});



Route::middleware('auth:api')->group(function () {
    //Profile Routes
    Route::get('/profile/{id}', [ProfileController::class, 'show']);
    Route::post('/users/{id}/updateProfile', [ProfileController::class, 'update']);
    Route::put('/users/{userId}/changePassword',[AuthController::class,'changePassword']);
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
    // Lấy tất cả bài viết của pet
    Route::get('pets/{petId}/posts', [PetController::class, 'getAllPostsByPet']);
    // Post route
    Route::get('posts', [PostController::class, 'getAllPosts']);
    Route::post('posts', [PostController::class, 'create']);
    Route::patch('posts/{id}', [PostController::class, 'update']);
    Route::delete('posts/{id}', [PostController::class, 'delete']);
    Route::put('/posts/{postId}/groups',[PostController::class, 'managePost']);
    Route::post('/posts/refresh', [PostController::class, 'refreshFeed']);
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

    // user Follow routes
    // Follow / Unfollow
    Route::post('users/{id}/follow', [FollowController::class, 'follow'])
        ->middleware('throttle:follow-unfollow');
    Route::delete('users/{id}/follow', [FollowController::class, 'unfollow'])
        ->middleware('throttle:follow-unfollow'); // unfollow user
    // Pet Follow / Unfollow
    Route::post('pets/{id}/follow', [PetFollowController::class, 'follow'])
        ->middleware('throttle:follow-unfollow');
    Route::delete('pets/{id}/follow', [PetFollowController::class, 'unfollow'])
        ->middleware('throttle:follow-unfollow'); // unfollow pet
    // Danh sách followers và following
    Route::get('users/{id}/followers', [ProfileController::class, 'getFollowers']);
    Route::get('users/{id}/following', [ProfileController::class, 'getFollowing']);


    // Search routes
    Route::get('/search', [SearchController::class, 'search']);
    Route::get('/search/join-group-requests/{groupId}',[SearchController::class, 'searchRequestJoinGroup']);
    Route::get('/search/groups/{groupId}/users',[SearchController::class, 'searchUsersOfGroup']);
    Route::get('/search/conversations',[SearchController::class,'searchConversation']);

    // Conversation routes
    Route::get('conversations', [ConversationController::class, 'index']);

    Route::get('conversations/recent', [ConversationController::class, 'recent']);
    Route::post('conversations', [ConversationController::class, 'store']);
    Route::delete('conversations/{conversationId}', [ConversationController::class, 'destroy']);
    Route::get('/conversations/{conversationId}/members',[ConversationController::class,'getMemberOfConversation']);
    Route::put('/conversations/{conversationId}/read', [ConversationController::class, 'update']);
    Route::get('/conversations/{conversationId}/add-members',[ConversationController::class,'getMembersToInvite']);
    Route::post('/conversations/{conversationId}/add-members',[ConversationController::class,'inviteMembers']);
    Route::delete('/conversations/{conversationId}/members/{targetId}', [ConversationController::class, 'deleteMember']);
    Route::put('/conversations/{conversationId}/updateInfo',[ConversationController::class,'updateConversation']);
    Route::put('/conversations/{conversationId}/changeRole/users/{targetId}',[ConversationController::class,'changeRole']);
    Route::delete('/conversations/{conversationId}/delete',[ConversationController::class,'delete']);
    Route::apiResource('conversations.messages', MessageController::class)
        ->shallow();
    // shallow() cho phép gọi messages/:id thay vì conversations/:conversationId/messages/:id
    Route::put('conversations/{conversationId}/read', [ConversationController::class, 'update']);


     // 📋 HEALTH CATEGORY ROUTES
    // -----------------------------
    Route::post('/health-categories', [HealthCategoryController::class, 'store']);
    Route::get('/health-categories/{petId}', [HealthCategoryController::class, 'index']);
    Route::put('/health-categories/{id}', [HealthCategoryController::class, 'update']);
    Route::delete('/health-categories/{id}', [HealthCategoryController::class, 'destroy']);
    Route::get('/health-categories/species/{species}', [HealthCategoryController::class, 'getBySpecies']);

    // -----------------------------
    // 🩺 PET HEALTH LOG ROUTES
    // -----------------------------
    Route::post('/pet-health-logs', [PetHealthLogController::class, 'store']);
    Route::put('/pet-health-logs/{id}', [PetHealthLogController::class, 'update']);
    Route::delete('/pet-health-logs/{id}', [PetHealthLogController::class, 'destroy']);
    Route::get('/pet-health-logs/pet/{petId}', [PetHealthLogController::class, 'getLogsByPetId']);


    // group routes
    Route::post('/groups', [GroupController::class, 'createGroup']);
    Route::get('/groups/{id}', [GroupController::class, 'getGroupById']);
    Route::put('/groups/{group}', [GroupController::class, 'updateGroup']);
    Route::delete('/groups/{group}', [GroupController::class, 'deleteGroup']);
    Route::get('/users/{userId}/groups', [GroupController::class, 'getGroupsByUserId']);
    Route::get('/users/{userId}/groups-attended', [GroupController::class, 'getGroupOfUserAttended']);
    Route::post('/groups/{groupId}/members', [GroupController::class, 'addMemberToGroup']);
    Route::get('/groups/{groupId}/members',[GroupController::class, 'getMembers'] );


    //question and answer routes
    Route::post('/groups/questions',[MembershipQuestionController::class,'create']);
    Route::get('/groups/{groupId}/questions',[MembershipQuestionController::class, 'index']);
    Route::put('/groups/questions/{questionId}',[MembershipQuestionController::class,'update']);
    Route::delete('/groups/questions/{questionId}',[MembershipQuestionController::class,'delete']);
    Route::post('/groups/{groupId}/joinGroup',[MembershipQuestionController::class,'joinGroup']);
    Route::get('/groups/{groupId}/joinRequests',[MembershipQuestionController::class,'getJoinRequests']);
    Route::get('/groups/{groupId}/posts',[PostController::class, 'getPostsOfGroup']);
    Route::get('/groups/{groupId}/postsPending',[PostController::class, 'getPostsPendingOfGroup']);


    Route::put('/requests/{requestId}',[MembershipQuestionController::class, 'updateRequest']);
    Route::delete('/requests/{groupId}',[MembershipQuestionController::class, 'deleteRequest']);


    //group member routes
    Route::delete('/groups/{groupId}/members/{userId}',[GroupMemberController::class, 'deleteMember']);


    //reports route
    Route::post('/reports',[ReportController::class,'create']);
    Route::put('/reports/update-status',[ReportController::class,'bulkUpdateStatus']);
    Route::delete('/reports/bulk-delete',[ReportController::class,'bulkDelete']);
    Route::get('/reports/groups/{groupId}',[ReportController::class,'getReportOfGroup']);



    // notification route
    Route::get('/notifications/{userId}',[NotificationController::class,'index']);
    Route::put('/notifications/{notificationId}/read',[NotificationController::class,'readNotification']);
    Route::post('/notifications/handleInvitation',[ConversationController::class,'handleInvitation']);
    
;   
});
