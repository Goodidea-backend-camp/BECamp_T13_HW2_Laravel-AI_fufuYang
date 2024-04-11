<?php

namespace App\AI;


use OpenAI;

class Chat
{
    protected array $messages = [];
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

    public function send(string $message): ?string
    {
        $this->messages[] = [
            "role" => "assistant",
            "content" => $message
        ];

        $client = OpenAI::client(config('services.openai.api_key'));

        $response = $client->chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => $this->messages,
        ])->choices[0]->message->content;



//        $response = Http::withToken(config('services.openai.api_key'))
//            ->post('https://api.openai.com/v1/chat/completions', [
//                "model" => "gpt-3.5-turbo",
//                "messages" => $this->messages
//            ])
//            ->json('choices.0.message.content');

        if ($response) {
            $this->messages[] = [
                "role" => "assistant",
                "content" => $response
            ];
        }

        return $response;
    }

    public function reply(string $message): ?string
    {
        return $this->send($message);
    }
}