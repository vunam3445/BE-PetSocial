<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
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

//Profile Routes

Route::middleware('auth:api')->group(function () {
    Route::get('/profile/{id?}', [ProfileController::class, 'show']);
});