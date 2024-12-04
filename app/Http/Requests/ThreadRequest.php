<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ThreadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'type' => 'nullable|in:chat,image',  // 這裡將 `type` 設為可選
        ];

        if ($this->isMethod('post')) {
            // 如果是創建新 Thread (POST 請求)，則需要 `type` 欄位
            $rules['type'] = 'required|in:chat,image';
        }

        return $rules;
    }
}
