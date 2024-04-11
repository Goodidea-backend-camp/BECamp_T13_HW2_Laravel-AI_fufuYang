<?php

namespace App\AI;


use OpenAI;

class Chat
{
    protected array $messages = [];

    private OpenAI\Client $client;

    public function __construct()
    {
        $this->client = OpenAI::client(config('services.openai.api_key'));
    }

    public function messages()
    {
        return $this->messages;
    }

    public function systemMessage(string $message): static
    {
        $this->messages[] = [
            "role" => "system",
            "content" => $message
        ];

        return $this;
    }

    public function send(string $message, ?bool $speech): ?string
    {
        $this->messages[] = [
            "role" => "assistant",
            "content" => $message
        ];

        $response = $this->client->chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => $this->messages,
        ])->choices[0]->message->content;

        if ($response) {
            $this->messages[] = [
                "role" => "assistant",
                "content" => $response
            ];
        }

        return $speech ? $this->speech($response) : $response;
    }

    public function speech(string $message) :string
    {
        return $this->client->audio()->speech([
            'model' => 'tts-1',
            'input' => $message,
            'voice' => 'alloy',
        ]);
    }

    public function reply(string $message): ?string
    {
        return $this->send($message, false);
    }
}