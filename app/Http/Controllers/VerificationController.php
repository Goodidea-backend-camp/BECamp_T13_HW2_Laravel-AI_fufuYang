<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function verify(Request $request, $token)
    {
        $user = User::where('verification_token', $token)->first();

        if (! $user) {
            return redirect(route('login'))->with('error', '無效的驗證令牌。');
        }

        $user->is_verified = true;
        $user->verification_token = null; // 清除驗證令牌
        $user->save();

        return redirect(route('login'))->with('success', '您的電子郵件已成功驗證，請登入。');
    }
}
