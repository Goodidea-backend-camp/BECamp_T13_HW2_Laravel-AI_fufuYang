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
        Schema::create('voice_messages', function (Blueprint $table) {
            $table->id(); // unsignedBigInteger primary key
            $table->foreignId('message_id')->constrained('chat_messages')->onDelete('cascade'); // 外鍵連接 chat_messages 表
            $table->string('audio_url'); // 語音檔路徑
            $table->timestamps(); // created_at
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voice_messages');
    }
};
