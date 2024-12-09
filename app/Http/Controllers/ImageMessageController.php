<?php

namespace App\Http\Controllers;

use App\AI\Assistant;
use App\Enums\SubscriptionType;
use App\Models\ImageMessage;
use App\Models\Thread;
use Illuminate\Http\Request;

class ImageMessageController extends Controller
{
    /**
     * Display a listing of the resource.
     * 顯示所有訊息
     */
    public function index($threadId)
    {
        $thread = Thread::findOrFail($threadId);
        $messages = $thread->imageMessages;

        return response()->json([
            'messages' => $messages,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() {}

    /**
     * Store a newly created resource in storage.
     * 儲存新訊息並發送給 OpenAI
     */
    public function store(Request $request, $threadId)
    {
        $user = auth()->user();  // 獲取當前用戶
        $thread = Thread::findOrFail($threadId);

        // 檢查免費會員的討論串數量
        if ($user->subscription_type == SubscriptionType::isFree) {
            // 查詢當前用戶創建的 "active" 討論串數量
            $activeThreadsCount = $thread->imageMessages()->count();

            // 如果該用戶已經有 10 個聊天訊息，則返回錯誤
            if ($activeThreadsCount >= 10) {
                return response()->json([
                    'status' => 'error',
                    'message' => $activeThreadsCount . '免費會員最多只能創建 10 個聊天訊息。',
                ], 400);
            }
        }

        $request->validate([
            'description' => 'required|string|max:500',
        ]);

        $thread = Thread::findOrFail($threadId);

        $assistant = new Assistant();

        $image_url = $assistant->visualize($request->description, [
            'response_format' => 'url',
        ]);

        $this->saveMessage($request->description, $image_url, $thread->id);

        //返回回應結果
        return response()->json([
            'message' => $activeThreadsCount . '訊息已發送並收到回應',
            'openai_response' => $image_url, // 回傳圖片的 URL
        ]);
    }

    public function saveMessage(string $description, string $image_url, int $threadId): ImageMessage
    {
        // 創建並儲存訊息
        $message = new ImageMessage();
        $message->description = $description;
        $message->image_url = $image_url;  // 使用 Assistant 回傳的 image_url
        $message->thread_id = $threadId;
        $message->save();

        return $message;
    }
}
