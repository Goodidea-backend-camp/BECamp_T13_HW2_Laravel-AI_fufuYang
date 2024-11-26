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
        Schema::create('threads', function (Blueprint $table) {
            $table->id(); // unsignedBigInteger primary key
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // 外鍵連接 users 表
            $table->string('title'); // 文字：threads的名稱
            $table->unsignedTinyInteger('type'); // 1=chat, 2=image
            $table->timestamps(); // created_at, updated_at
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('threads');
    }
};
