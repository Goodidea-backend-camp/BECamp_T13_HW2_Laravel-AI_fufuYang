<?php

namespace App\Services;

use App\Models\User;
use App\Enums\Provider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerificationEmail;
use Laravel\Socialite\Facades\Socialite;
use App\AI\Assistant;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Contracts\Auth\Authenticatable;

class AuthService
{

    // 註冊邏輯
    public function register(array $data)
    {
        // 檢查是否已存在相同 email 的用戶
        $existingUser = User::where('email', $data['email'])->first();
        if ($existingUser) {
            // 檢查是否為第三方登入
            if ($existingUser->password == null) {
                return  ['email_error' => '請直接使用 Google 登入', 'status' => Response::HTTP_CONFLICT]; // 如果是第三方登入，提示使用 Google 登入
            }

            return ['name_error' => '電子郵件已被使用，請選擇其他電子郵件地址', 'status' => Response::HTTP_CONFLICT];  // 如果是本地註冊用戶，提示更換郵件地址
        }

        // 檢查使用者名稱是否違反善良風俗
        $assistant = new Assistant();
        $nameCheck = $assistant->checkNameValidity($data['name']); // 檢查名稱

        if (!$nameCheck['is_valid']) {
            return ['name_error' => '使用者名稱違反公共道德，請更改', Response::HTTP_UNPROCESSABLE_ENTITY]; // 使用者名稱不合法
        }


        // 註冊新用戶
        $user = new User();
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->verification_token = Str::random(32);
        $user->password = $data['password'];
        $user->introduction = $data['introduction'];
        $user->profile_image_url = 'pending';

        // 根據是否提供密碼來判斷 provider 來源
        $user->provider = $data['password'] ? Provider::LOCAL : Provider::GOOGLE;

        // 生成大頭照
        $profileImageUrl = $assistant->visualize($data['introduction'], [
            'response_format' => 'url',
        ]);
        $user->profile_image_url = $profileImageUrl;

        // 保存新用戶
        $user->save();

        // 發送驗證郵件
        Mail::to($user->email)->send(new VerificationEmail($user));

        return $user;
    }
    // Google 登入邏輯
    public function handleGoogleLogin()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        // 獲取 Google 用戶資料
        $googleUser = Socialite::driver('google')->user();

        // 嘗試查找已經存在的用戶
        $user = User::where('email', $googleUser->email)->first();

        if ($user) {
            // 如果用戶已經存在，更新其資料
            $user->provider_id = $googleUser->id;
            $user->name = $googleUser->name;
            // 更新 Google 提供的資料
            $user->save();
        } else {
            // 如果用戶不存在，創建新用戶
            $user = new User();
            $user->provider_id = $googleUser->id;
            $user->name = $googleUser->name;
            $user->email = $googleUser->email;
            // 設定 provider 為 Google 登入
            $user->provider = Provider::GOOGLE;

            // 保存新用戶
            $user->save();
        }

        // 使用 Auth 登入
        Auth::login($user);

        return $user;
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

    // 更新用戶資料
    public function updateUser(Authenticatable $user, Request $request)
    {
        if ($user instanceof User) {
            $user->name = $request->input('name');
            $user->introduction = $request->input('introduction');
            $user->save();
        }

        return $user;
    }
    // 會員登出邏輯
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
    }
}
