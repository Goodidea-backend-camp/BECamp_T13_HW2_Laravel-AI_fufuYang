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
}
