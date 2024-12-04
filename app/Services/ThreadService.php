<?php

namespace App\Services;

use App\Models\Thread;
use App\Enums\ThreadType;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ThreadService
{
    public function __construct() {}

    /**
     * 獲取所有的討論串
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllThreads()
    {
        return Thread::all();
    }

    /**
     * 創建新的討論串
     *
     * @param array $data
     * @return Thread
     */
    public function createThread(array $data)
    {
        $userId = Auth::id();  // 取得當前用戶 ID
        $type = $data['type'] === 'chat' ? ThreadType::Chat : ThreadType::Image;

        $thread = new Thread();
        $thread->title = $data['title'];
        $thread->type = $type;  // 儲存 Enum 的數字值（1 或 2）
        $thread->user_id = $userId;
        $thread->save();
        return $thread;
    }
}
