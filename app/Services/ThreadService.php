<?php

namespace App\Services;

use App\Enums\ThreadType;
use App\Models\Thread;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ThreadService
{

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

    /**
     * 根據 ID 獲取指定的討論串
     *
     * @param  int  $id
     * @return Thread
     */
    public function getThreadById($id)
    {
        return Thread::findOrFail($id);
    }

    /**
     * 更新指定 ID 的討論串
     *
     * @param  int  $id
     * @return array
     */
    public function updateThread($id, array $data)
    {
        $thread = Thread::find($id);

        if (! $thread) {
            return [
                'status' => 'error',
                'message' => '找不到可以更新的項目',
                'code' => Response::HTTP_NOT_FOUND,
            ];
        }
        $thread->title = $data['title'];

        // 如果沒有傳遞 `type`，設置預設值為 Chat 類型
        if (! isset($data['type'])) {
            $data['type'] = ThreadType::Chat;
        }

        $thread->save();

        return [
            'status' => 'success',
            'thread' => $thread,
            'message' => '名稱更新成功',
            'code' => Response::HTTP_OK,
        ];
    }

    /**
     * 刪除指定 ID 的討論串
     *
     * @param  int  $id
     * @return void
     */
    public function deleteThread($id)
    {
        $thread = Thread::findOrFail($id);
        $thread->delete();
    }
}
