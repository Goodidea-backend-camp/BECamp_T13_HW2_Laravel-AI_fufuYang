<?php

namespace App\Models;

use App\Enums\MessageRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChatMessage extends Model
{
    use HasFactory;

    // 反向一對多關聯：每條聊天消息屬於一個討論串
    public function thread(): BelongsTo
    {
        return $this->belongsTo(Thread::class);
    }

    // 一對一關聯：每條聊天消息對應一條語音消息
    public function voiceMessage(): HasOne
    {
        return $this->hasOne(VoiceMessage::class);
    }

    // 讓 Eloquent 使用 enum 來處理 'role' 欄位
    protected $casts = [
        'role' => MessageRole::class,  // 使用 Enum 類型
    ];
}
