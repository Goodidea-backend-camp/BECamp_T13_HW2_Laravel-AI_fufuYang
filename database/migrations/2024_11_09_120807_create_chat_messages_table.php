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
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id(); // unsignedBigInteger primary key
            $table->foreignId('thread_id')->constrained('threads')->onDelete('cascade'); // 外鍵連接 threads 表
            $table->unsignedTinyInteger('role'); // 1=user, 2=system
            $table->text('content'); // 訊息內容
            $table->timestamps(); // created_at
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
