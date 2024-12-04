<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 *  @property int $id 主鍵 ID
 */
class Thread extends Model
{
    use HasFactory;

    /**
     * 反向一對多關聯：每個討論串屬於一個用戶
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 一對多關聯：每個討論串可以有多條聊天消息
     *
     * @return HasMany
 
     */
    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'thread_id');
    }

    /**
     * 一對多關聯：每個討論串可以有多條圖片消息
     *
     * @return HasMany
     */
    public function imageMessages(): HasMany
    {
        return $this->hasMany(ImageMessage::class);
    }
}
