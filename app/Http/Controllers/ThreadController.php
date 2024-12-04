<?php

namespace App\Http\Controllers;

use App\Enums\SubscriptionType;
use App\Http\Requests\ThreadRequest;
use App\Http\Resources\ThreadResource;
use App\Services\ThreadService;
use App\Traits\ApiResponses;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ThreadController extends Controller
{
    use ApiResponses;
    use AuthorizesRequests;

    /** @var ThreadService */
    protected $threadService;

    /**
     * @param ThreadService $threadService
     */
    public function __construct(ThreadService $threadService)
    {
        $this->threadService = $threadService;
    }

    /**
     * 顯示所有的討論串
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $threads = $this->threadService->getAllThreads();
        return $this->success(ThreadResource::collection($threads));
    }

    /**
     * 儲存一個新創建的討論串
     *
     * @param ThreadRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ThreadRequest $request)
    {
        $user = auth()->user();

        // 檢查用戶的訂閱類型，如果是免費會員，則限制最多只能創建 3 個討論串
        if ($user->subscription_type == SubscriptionType::isFree) {
            $activeThreadsCount = $user->threads->count();

            // 如果免費會員已經創建了 3 個討論串，返回錯誤訊息
            if ($activeThreadsCount >= 3) {
                return response()->json([
                    'status' => 'error',
                    'message' => $activeThreadsCount . '免費會員最多只能創建 3 個討論串。',
                ], 400);
            }
        }

        // 創建新的討論串
        $thread = $this->threadService->createThread($request->validated());
        return $this->success(new ThreadResource($thread), 'Thread 創建成功');
    }

    /**
     * 顯示指定 ID 的討論串
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $thread = $this->threadService->getThreadById($id);
        return $this->success(new ThreadResource($thread));
    }
}
