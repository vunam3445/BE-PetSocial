<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PetController;

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
        Route::post('logout',[AuthController::class, 'logout']);      // POST /api/auth/logout
    });
});



Route::middleware('auth:api')->group(function () {
    //Profile Routes
    Route::get('/profile/{id?}', [ProfileController::class, 'show']);


    //pet Routes
     // Lấy danh sách pet theo user_id (nếu có)
    Route::get('pets/user/{userId?}', [PetController::class, 'getAllPetsByUser']);

    // Tạo mới pet
    Route::post('pets', [PetController::class, 'create']);

    // Lấy chi tiết 1 pet
    Route::get('pets/{petId}', [PetController::class, 'show']);

    // Cập nhật pet
    Route::put('pets/{petId}', [PetController::class, 'update']);

    // Xoá pet
    Route::delete('pets/{petId}', [PetController::class, 'delete']);
});