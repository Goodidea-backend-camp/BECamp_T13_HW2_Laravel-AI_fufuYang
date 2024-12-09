<?php

namespace App\Traits;

trait ApiResponses
{
    // 成功回應，並帶有資料和狀態碼
    protected function success($message, $data = [], $statusCode = 200)
    {
        return response()->json([
            'data' => $data,
            'message' => $message,
            'status' => $statusCode,
        ], $statusCode);
    }

    // 通用錯誤回應
    protected function error($errors = [], $statusCode = 400)
    {
        if (is_string($errors)) {
            return response()->json([
                'message' => $errors,
                'status' => $statusCode,
            ], $statusCode);
        }

        return response()->json([
            'errors' => $errors,
        ], $statusCode);
    }

    // 無授權錯誤
    protected function notAuthorized($message)
    {
        return $this->error($message, 401);
    }

    // 資源未找到錯誤
    protected function notFound($message)
    {
        return $this->error($message, 404);
    }

    // 註冊成功回應
    protected function registered($message, $data = [])
    {
        return $this->success($message, $data, 201);
    }
}
