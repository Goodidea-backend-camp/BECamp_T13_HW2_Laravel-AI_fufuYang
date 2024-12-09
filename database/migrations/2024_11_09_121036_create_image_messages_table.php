<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('image_messages', function (Blueprint $table) {
            $table->id(); // unsignedBigInteger primary key
            $table->foreignId('thread_id')->constrained('threads')->onDelete('cascade'); // 外鍵連接 threads 表
            $table->text('description'); // 描述要生成的內容
            $table->text('image_url'); // 生成的圖片路徑
            $table->timestamps(); // created_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('image_messages');
    }
};
