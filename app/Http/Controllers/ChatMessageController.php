<?php

namespace App\Http\Controllers;

use App\AI\Assistant;
use App\Enums\MessageRole;
use App\Enums\SubscriptionType;
use App\Models\ChatMessage;
use App\Models\VoiceMessage;
use Illuminate\Http\Request;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class ChatMessageController extends Controller
{
    /**
     * 取得特定討論串中的所有訊息
     *
     * @param int $threadId 討論串的 ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(int $threadId): \Illuminate\Http\JsonResponse
    {
        $thread = Thread::findOrFail($threadId);
        $messages = $thread->chatMessages()->get();
        return response()->json([
            'messages' => $messages
        ]);
    }

    /**
     * 儲存新訊息並將其發送至 OpenAI 獲取回應
     *
     * @param \Illuminate\Http\Request $request
     * @param int $threadId 討論串的 ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, int $threadId): \Illuminate\Http\JsonResponse
    {
        /** @var User $user */
        $user = auth()->user(); // 獲取當前用戶
        /**
         * @var Thread $thread
         */
        $thread = Thread::findOrFail($threadId);

        // 檢查免費會員的討論串數量
        if ($user->subscription_type == SubscriptionType::isFree->value) {
            $activeThreadsCount = $thread->chatMessages()->getQuery()->where('role', MessageRole::User)->count();

            // 如果該用戶已經有 10 個聊天訊息，則返回錯誤
            if ($activeThreadsCount >= 10) {
                return response()->json([
                    'status' => 'error',
                    'message' => $activeThreadsCount . ' 免費會員最多只能創建 10 個聊天訊息。',
                ], 400);
            }
        }

        // 驗證傳入的訊息內容
        $request->validate([
            'content' => 'required|string|max:500',
        ]);

        $this->saveMessage($request->content, MessageRole::User, $thread->id);
        $messages = $this->getMessagesForThread($threadId);

        // 創建 Assistant 實例來與 OpenAI 交互
        $assistant = new Assistant();

        $historyMessages = collect($messages)->map(function ($msg) {
            return $msg->content;
        })->implode(' ');

        $openAiResponse = $assistant->send($historyMessages . ' ' . $request->content, false);

        // 儲存 OpenAI 的回應訊息
        $this->saveMessage($openAiResponse, MessageRole::Assistant, $thread->id);

        // 檢查是否要求語音回應
        if ($request->has('speech') && $request->speech === true) {
            $this->handleVoiceResponse($openAiResponse, $thread);
        }

        return response()->json([
            'message' => '訊息已發送並收到回應，文字回覆',
            'openai_response' => $openAiResponse
        ]);
    }


    /**
     * 取得特定訊息的詳細資訊
     *
     * @param int $threadId 討論串的 ID
     * @param int $id 訊息的 ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $threadId, int $id): \Illuminate\Http\JsonResponse
    {
        $message = $this->getMessageById($threadId, $id);

        return response()->json([
            'message' => $message
        ]);
    }
    /**
     * 處理語音回應生成
     *
     * @param string $openAiResponse OpenAI 回應的文字內容
     * @param \App\Models\Thread $thread 討論串實例
     * @return \Illuminate\Http\JsonResponse
     */
    private function handleVoiceResponse(string $openAiResponse, Thread $thread): \Illuminate\Http\JsonResponse
    {
        $assistant = new Assistant();
        $audioResponse = $assistant->speech($openAiResponse);
        $audioFilePath = 'audio_files/' . uniqid('audio_') . '.mp3';

        Storage::disk('public')->put($audioFilePath, $audioResponse);
        $audioUrl = Storage::url($audioFilePath);

        $voiceMessage = new VoiceMessage();
        $voiceMessage->message_id = $thread->chatMessages()->latest()->first()->id;
        $voiceMessage->audio_url = $audioUrl;
        $voiceMessage->save();

        return response()->json([
            'message' => '訊息已發送並收到回應，回傳語音訊息',
            'openai_response' => $openAiResponse,
            'audio_response' => $audioUrl,
        ]);
    }

    /**
     * 儲存訊息到資料庫
     *
     * @param string $content 訊息內容
     * @param string $role 訊息的角色（用戶或助手）
     * @param int $threadId 訊息所屬的討論串 ID
     * @return ChatMessage
     */
    private function saveMessage(string $content, string $role, int $threadId): ChatMessage
    {
        $message = new ChatMessage();
        $message->content = $content;
        $message->role = $role;
        $message->thread_id = $threadId;
        $message->save();

        return $message;
    }

    /**
     * 取得特定討論串中的訊息，並按創建時間排序
     *
     * @param int $threadId 討論串的 ID
     * @return \App\Models\ChatMessage[]
     */
    private function getMessagesForThread(int $threadId): \Illuminate\Database\Eloquent\Collection
    {
        return ChatMessage::where('thread_id', $threadId)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
    }


    /**
     * 取得特定討論串中的訊息，通過訊息 ID 查詢
     *
     * @param int $threadId 討論串的 ID
     * @param int $id 訊息的 ID
     * @return ChatMessage
     */
    private function getMessageById(int $threadId, int $id): ChatMessage
    {
        $thread = Thread::findOrFail($threadId);
        return $thread->chatMessages()->findOrFail($id);
    }
}
