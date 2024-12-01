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

    // =================================
    // 會員登入
    public function login()
    {
        return view("auth.login");
    }

    public function loginPost(Request $request)
    {
        // 登入驗證
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        $credentials = $request->only('email', 'password');

        // 登入失敗
        if (!Auth::attempt($credentials)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => '帳號或密碼錯誤，請重新輸入。'
                ], Response::HTTP_BAD_REQUEST);
            }
            return redirect(route('login'))->with([
                'error' => '登入失敗，請重新輸入。'
            ]);
        }

        // 登入
        $user = User::firstWhere('email', $request->email);

        // 檢查使用者是否已完成電子郵件驗證
        if (!$user->is_verified) {
            // 若未驗證，提示用戶完成驗證後再登入
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => '請完成電子郵件驗證後再登入。',
                ], Response::HTTP_FORBIDDEN);
            }

            return redirect(route('login'))->with([
                'error' => '請完成電子郵件驗證後再登入。',
            ]);
        }

        // 產生 API token
        $token = $user->createToken(
            'API token for ' . $user->name,
            ['*'],
            now()->addMonth() // 一個月後過期
        )->plainTextToken;

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'access_token' => $token,
                'message' => '成功登入',
            ], Response::HTTP_OK);
        }
        return redirect()->intended(route('theards.index'));
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
