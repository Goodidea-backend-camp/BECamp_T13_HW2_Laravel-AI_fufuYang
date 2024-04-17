<?php

namespace App\AI;

use OpenAI;

class Assistant
{
    protected array $messages = [];

    public OpenAI\Client $client;

    public function __construct(array $messages = [])
    {
        $this->client = OpenAI::client(config('services.openai.api_key'));
        $this->messages = $messages;
    }

    public function hello()
    {
        echo 'hello world';
    }

    public function messages()
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

        return $speech ? $this->speech($response) : $response;
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

        $url = $this->client->images()->create($options)->data[0]->url;

        $this->addMessage($url, 'assistant');

        return $url;
    }

    public function reply(string $message): ?string
    {
        return $this->send($message, false);
    }

    protected function addMessage(string $message, string $role = 'user'): static
    {
        $this->messages[] = [
            'role' => $role,
            'content' => $message,
        ];

        return $this;
    }
}
