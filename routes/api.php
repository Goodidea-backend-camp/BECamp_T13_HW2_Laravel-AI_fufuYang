<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\ThreadController;
use App\Http\Controllers\ChatMessageController;
use App\Http\Controllers\ImageMessageController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum'); // 需要認證才能存取

// 認證路由
Route::get('/login', [AuthController::class, "login"])->name("login"); // 顯示登入頁面
Route::post('/login', [AuthController::class, "loginPost"])->name("login.post"); // 處理登入表單
Route::get('/register', [AuthController::class, "register"])->name("register"); // 顯示註冊頁面
Route::post('/register', [AuthController::class, "registerPost"])->name("register.post"); // 處理註冊表單
Route::get('/verify/{token}', [VerificationController::class, 'verify'])->name('verification.verify'); // 使用者驗證

// Google 登入路由
Route::middleware(['web'])->get('/auth/google/redirect', [AuthController::class, 'googleRedirect']); // Google 登入重定向
Route::middleware(['web'])->get('/auth/google/callback', [AuthController::class, 'googleCallback']); // Google 登入回調

// 需要登入才能訪問的路由
Route::middleware('auth:sanctum')->group(function () {
    // 使用者個人資料相關路由
    Route::get('/users/{id}', [AuthController::class, 'edit'])->name('profile.edit'); // 顯示使用者編輯頁面
    Route::put('/users/{id}', [AuthController::class, 'update'])->name('profile.update'); // 更新使用者資料
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout'); // 顯示登出頁面
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout.post'); // 處理登出

    // 討論串的資源路由
    Route::resource('threads', ThreadController::class)->except(['create', 'edit']);

    // 討論串內訊息的資源路由
    Route::resource('threads.messages', ChatMessageController::class)->shallow()->except(['create', 'edit']);

    // 討論串內圖片訊息的資源路由
    Route::resource('threads.images', ImageMessageController::class)->shallow()->except(['create', 'edit']);
});
