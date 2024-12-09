<?php

namespace App\AI;

use OpenAI;

class Assistant
{
    public OpenAI\Client $client;

    public function __construct(protected array $messages = [])
    {
        $this->client = OpenAI::client(config('services.openai.api_key'));
    }

    public function hello(): void
    {
        echo 'hello world';
    }

    public function messages(): array
    {
        return $this->messages;
    }

    public function systemMessage(string $message): static
    {
        $this->addMessage($message, 'system');

        return $this;
    }

    public function send(string $message, ?bool $speech): ?string
    {
        $this->addMessage($message, 'assistant');

        $response = $this->client->chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => $this->messages,
        ])->choices[0]->message->content;

        if ($response) {
            $this->addMessage($response, 'assistant');
        }

        return $speech === true ? $this->speech($response) : $response;
    }

    public function speech(string $message): string
    {
        return $this->client->audio()->speech([
            'model' => 'tts-1',
            'input' => $message,
            'voice' => 'alloy',
        ]);
    }

    public function visualize(string $description, array $options = [])
    {
        $this->addMessage($description);

        $description = collect($this->messages)
            ->where('role', 'user')
            ->pluck('content')
            ->implode(' ');

        $options = array_merge([
            'prompt' => $description,
            'model' => 'dall-e-3',
        ], $options);

        try {
            $url = $this->client->images()->create($options)->data[0]->url;

            $this->addMessage($url, 'assistant');

            return $url;
        } catch (\Exception $e) {
            // 捕獲並處理錯誤
            return ['error' => '圖片生成失敗，請稍後再試'];
        }
    }

    public function reply(string $message): ?string
    {
        return $this->send($message, false);
    }

    public function addMessage(string $message, string $role = 'user'): static
    {
        $this->messages[] = [
            'role' => $role,
            'content' => $message,
        ];

        return $this;
    }

    public function checkNameValidity(string $name): array
    {
        // 使用 OpenAI 的內容審核 API，檢查提供的名稱是否合法
        $response = $this->client->moderations()->create([
            'model' => 'omni-moderation-latest', // 使用的模型
            'input' => $name, // 檢查的名稱
        ]);

        // 返回檢查結果，包括名稱是否有效和詳細信息
        return [
            'is_valid' => $response->results[0]->flagged === false, // 如果 flagged 為 false，表示名稱是有效的
            'details' => $response->results[0]->categories, // 返回標籤類別，用於進一步分析
        ];
    }
}
