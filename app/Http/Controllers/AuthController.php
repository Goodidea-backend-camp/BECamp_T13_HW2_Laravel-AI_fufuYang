<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Traits\ApiResponses;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    use ApiResponses;

    public function __construct(protected AuthService $authService) {}

    // [註冊]==============================================
    public function register(): View
    {
        return view('auth.register');
    }

    public function registerPost(RegisterRequest $registerRequest)
    {
        // 註冊驗證
        $result = $this->authService->register($registerRequest->validated());

        // 如果註冊返回錯誤
        if (isset($registerRequest['email_error']) || isset($result['name_error'])) {
            if ($registerRequest->expectsJson()) {
                // 若是 JSON 請求，回傳錯誤訊息
                return $this->error($result['name_error'] ?? $result['email_error'], $result['status'] ?? Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // 若是表單請求，重定向並顯示錯誤訊息
            return redirect()->route('register')->withErrors([
                'email' => $result['email_error'] ?? null, // 根據不同的錯誤訊息，填寫錯誤訊息
                'name' => $result['name_error'] ?? null,
            ]);
        }

        // 註冊成功，回傳成功訊息
        if ($registerRequest->expectsJson()) {
            return $this->success(null, '註冊成功，請檢查您的電子郵件以驗證帳戶。');
        }

        // 若是表單請求，重定向回註冊頁面，並顯示成功訊息
        return redirect()->route('login')->with('status', '註冊成功，請檢查您的電子郵件以驗證帳戶。');
    }

    // [Google登入]==============================================
    public function googleRedirect(): RedirectResponse
    {
        return $this->authService->handleGoogleLogin();
    }

    public function googleCallback(Request $request): RedirectResponse|UserResource
    {
        // 直接處理 Google Callback，並傳回處理後的使用者資料
        $user = $this->authService->handleGoogleCallback();
        if ($request->expectsJson()) {
            return new UserResource($user);
        }

        return redirect()->intended(route('theards.index'));
    }

    public function login()
    {
        return view('auth.login');
    }

    // [登入]==============================================
    public function loginPost(LoginRequest $LoginRequest)
    {
        $credentials = $LoginRequest->only('email', 'password');

        // 呼叫 AuthService 來處理登入邏輯
        $response = $this->authService->login($credentials);

        // 如果是錯誤回應（未驗證或其他錯誤）
        if ($response['status'] == 'error') {
            if ($LoginRequest->expectsJson()) {
                return response()->json([
                    'status' => $response['status'],
                    'message' => $response['message'],
                    'code' => $response['code'],
                ]);
            }

            session()->flash('error', $response['message']);

            return redirect(route('login'));
        }

        // 如果是成功登入，返回 API token
        if ($LoginRequest->expectsJson()) {
            return response()->json([
                'status' => $response['status'],
                'access_token' => $response['access_token'],
                'message' => $response['message'],
            ], $response['code']);
        }

        // 登入成功後，重定向到其他頁面
        return redirect()->route('theards.index');
    }

    // [會員編輯]==============================================
    public function edit(): View
    {
        $user = Auth::user();

        return view('auth.edit', ['user' => $user]);
    }

    // [更新會員資料]==============================================
    public function update(Request $request): Response|View|JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'introduction' => 'nullable|string|max:255',
        ]);

        // 確保使用者已登錄
        $user = Auth::user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // 呼叫 AuthService 更新用戶資料
        $authenticatable = $this->authService->updateUser($user, $request);
        if ($request->expectsJson()) {
            return $this->success(new UserResource($authenticatable), '資料更新成功');
        }

        return view('auth.edit', ['user' => $user]);
    }

    // [登出]==============================================
    public function logout(Request $request): Response
    {
        // 呼叫 AuthService 來處理登出邏輯
        $this->authService->logout($request);

        // 判斷請求類型並返回響應
        if ($request->expectsJson()) {
            // 如果是 JSON 請求，返回空的 JSON 響應
            return response()->json(null, Response::HTTP_NO_CONTENT);
        }

        // 否則，重定向到登入頁面
        return redirect(route('login'));
    }
}
