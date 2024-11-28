<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Enums\Provider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use App\Mail\VerificationEmail;
use Illuminate\Support\Facades\Mail;
use App\AI\Assistant;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;


class AuthController extends Controller
{

    // 會員註冊
    public function register()
    {
        return view("auth.register");
    }

    public function registerPost(Request $request)
    {
        // 註冊驗證
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string',
            'introduction' => 'required|string',
        ]);

        // 檢查電子郵件是否已存在
        $existingUser = User::where('email', $request->email)->first();  // 檢查是否有相同email的用戶
        //$existingPassword = User::where('password', $request->password)->exists()
        if ($existingUser) {
            // 檢查是否為第三方登入的用戶，密碼是否存在，若無密碼則為第三方登入
            if ($existingUser->provider !== Provider::LOCAL) {
                // 如果存在第三方登入的用戶，則不能用相同的email進行本地註冊
                if ($request->expectsJson()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => '請直接使用 google 登入。',
                    ], Response::HTTP_CONFLICT);
                }

                return redirect()->route('register')->withErrors([
                    'email' => '請直接使用 google 登入。',
                ]);
            }


            // 檢查請求是否為 AJAX
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => '電子郵件已被使用，請選擇其他電子郵件地址'
                ], Response::HTTP_CONFLICT);
            }

            // 返回到登入頁面，顯示錯誤訊息
            return redirect()->route('register')->withErrors([
                'email' => '電子郵件已被使用，請選擇其他電子郵件地址。'
            ]);
        }

        // 檢查使用者名稱是否違反善良風俗
        $assistant = new Assistant();
        $nameCheck = $assistant->checkNameValidity($request->name); // 檢查名稱

        if (!$nameCheck['is_valid']) {
            // 如果名稱不合法，返回錯誤響應
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => '驗證失敗。',
                    'errors' => [
                        'name' => '使用者名稱違反公共道德，請更改。',
                    ],
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            return redirect()->route('register')->withErrors([
                'name' => '使用者名稱不符合道德規範，請使用其他名稱。'
            ]);
        }

        // 存入註冊資訊
        $user = new User();
        $user->name = $request['name'];
        $user->email = $request['email'];
        $user->verification_token = Str::random(32); // 生成驗證信的token
        $user->password = $request['password'];
        $user->introduction = $request['introduction'];
        $user->profile_image_url = 'pending';



        // 根據是否提供密碼來判斷 provider 來源
        $user->provider = $request->filled('password') ? Provider::LOCAL : Provider::GOOGLE;


        // 生成大頭照並轉換為 Base64
        $profileImageUrl = $assistant->visualize($request['introduction'], [
            'response_format' => 'url', // 可選擇 'url' 或 'base64'
        ]);
        $user->profile_image_url = $profileImageUrl;


        // 保存註冊資訊
        if ($user->save()) {
            // 發送驗證郵件
            Mail::to($user->email)->send(new VerificationEmail($user));
            // 判斷請求是否來自 AJAX
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => '註冊成功，請檢查您的電子郵件以驗證帳戶。',
                ]);
            }
            return redirect(route('login'))->with('success', '註冊成功，請檢查您的電子郵件以驗證帳戶。');
        }
    }
    // Google 登入 
    public function googleRedirect()
    {
        return Socialite::driver('google')->redirect();
    }
    // Google 登入
    public function googleCallback(Request $request)
    {
        // 獲取 Google 用戶資料
        $googleUser = Socialite::driver('google')->user();


        // 檢查是否已經有相同 email 的用戶
        $user = User::where('email', $googleUser->email)->first();  // 或使用 exists()

        if ($user) {
            // 如果用戶已經存在，更新其資料
            $user->provider_id = $googleUser->id;
            $user->name = $googleUser->name;
            // 保存更新後的資料
            $user->save();
        } else {
            // 如果用戶不存在，創建新用戶
            $user = new User();
            $user->provider_id = $googleUser->id;
            $user->name = $googleUser->name;
            $user->email = $googleUser->email;  // 這裡設定 email  
            // 根據是否提供密碼來判斷 provider 來源
            $user->provider = $request->filled('password') ? Provider::LOCAL : Provider::GOOGLE;

            // 保存新用戶
            $user->save();
        }

        // 使用 Auth 登入
        Auth::login($user);

        // 重定向到其他頁面
        return redirect()->route('theards.index');
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
