<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Enums\Provider;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;
use App\Services\AuthService;
use App\Traits\ApiResponses;
use Illuminate\Contracts\View\View;
use App\Http\Requests\RegisterRequest;
use Illuminate\Http\RedirectResponse;
use App\Http\Resources\UserResource;

class AuthController extends Controller
{
    use ApiResponses;

    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    // [註冊]==============================================
    public function register(): View
    {
        return view('auth.register');
    }

    public function registerPost(RegisterRequest $request)
    {
        // 註冊驗證
        $result = $this->authService->register($request->validated());

        // 如果註冊返回錯誤
        if (isset($request['email_error']) || isset($result['name_error'])) {
            if ($request->expectsJson()) {
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
        if ($request->expectsJson()) {
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
        return view("auth.login");
    }


    // 登入邏輯
    public function login(array $credentials)
    {


        // 查詢用戶
        $user = User::where('email', $credentials['email'])->first();

        // 檢查用戶是否存在
        if (!$user) {
            return [
                'status' => 'error',
                'message' => '請註冊帳號後再行登入',
                'code' => Response::HTTP_UNAUTHORIZED
            ];
        }

        // 如果用戶存在，檢查是否是第三方登入（沒有密碼的情況）
        if ($user->provider == Provider::GOOGLE) {
            return [
                'status' => 'error',
                'message' => '請使用 Google 登入',
                'code' => Response::HTTP_UNAUTHORIZED
            ];
        }
        // 嘗試根據憑證登入
        $loginSuccess = Auth::attempt($credentials);
        // 如果是本地用戶，檢查帳號密碼
        if (!$loginSuccess) {
            return [
                'status' => 'error',
                'message' => '電子郵件或密碼錯誤',
                'code' => Response::HTTP_UNAUTHORIZED
            ];
        }

        // 檢查是否已完成電子郵件驗證
        if (!$user->is_verified) {
            return [
                'status' => 'error',
                'message' => '請完成電子郵件驗證後再登入。',
                'code' => Response::HTTP_FORBIDDEN
            ];
        }

        // 登入成功，生成 API token
        $token = $user->createToken(
            'API token for ' . $user->name,
            ['*'],
            now()->addMonth() // 一個月後過期
        )->plainTextToken;

        return [
            'status' => 'success',
            'access_token' => $token,
            'user' => $user,
            'message' => '成功登入',
            'code' => Response::HTTP_OK
        ];
    }
    // 會員編輯
    public function edit(Request $request)
    {
        // 取得當前登入的使用者
        $user = Auth::user();

        // 顯示會員編輯頁面，並傳遞使用者資料
        return view('auth.edit', compact('user'));
    }


    // 會員資料更新
    public function update(Request $request)
    {
        // 驗證資料，僅允許更新 name 和 introduction
        $request->validate([
            'name' => 'required|string|max:255',
            'introduction' => 'nullable|string',
        ]);

        // 取得當前登入的使用者
        $user = Auth::user();

        // 更新 name 和 introduction

        $user->name = $request->input('name');
        $user->introduction = $request->input('introduction');
        $user->save();

        // 回傳更新成功的訊息
        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => '會員資料更新成功',
            ], Response::HTTP_OK);
        }

        return redirect()->route('profile.edit')->with('success', '會員資料更新成功');
    }
    // =================================
    // 會員登出

    public function logout(Request $request)
    {
        // 清除 Sanctum 的 token
        $request->user()->tokens->each(function ($token) {
            $token->delete();
        });

        // 清除 session 資料
        Session::flush();

        // 使用 Web guard 登出
        Auth::guard('web')->logout();

        // 判斷請求類型並返回響應
        if ($request->expectsJson()) {
            // 如果是 JSON 請求，返回空的 JSON 響應
            return response()->json(null, Response::HTTP_NO_CONTENT);
        }

        // 否則，重定向到登入頁面
        return redirect(route('login'));
    }
}
