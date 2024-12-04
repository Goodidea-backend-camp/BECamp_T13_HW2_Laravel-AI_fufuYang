<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImageMessage extends Model
{
    use HasFactory;

    // 反向一對多關聯：每條圖片消息屬於一個討論串
    public function thread(): BelongsTo
    {
        return $this->belongsTo(Thread::class);
    }
}
