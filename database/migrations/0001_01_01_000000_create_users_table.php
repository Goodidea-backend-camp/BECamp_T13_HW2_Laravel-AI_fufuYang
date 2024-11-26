<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->text('introduction')->nullable(); // 自我介紹
            $table->string('profile_image_url')->nullable(); // 大頭照路徑
            $table->string('verification_token')->nullable(); // 驗證信 token
            $table->boolean('is_verified')->default(false); // 是否完成驗證
            $table->unsignedTinyInteger('subscription_type')->default(1); // 會員制度 (1=free, 2=pro)
            $table->string('provider')->nullable(); // 第三方登入服務名稱
            $table->string('provider_id')->nullable(); // 第三方唯一用戶 ID
            $table->string('oauth_token')->nullable(); // 第三方的 OAuth token
            $table->timestamp('oauth_expires_at')->nullable(); // 第三方的 OAuth token 過期時間
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
