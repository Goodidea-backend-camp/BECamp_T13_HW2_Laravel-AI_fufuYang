<?php

namespace App\AI;

use OpenAI;
use OpenAI\Client;

/**
 * @note 這個 Class 還在測試階段，請不要使用或更動
 *
 * @phpstan-consistent-constructor
 */
class LaraparseAssistant
{
    public Client $client;

    protected OpenAI\Responses\Assistants\AssistantResponse $assistant;

    protected string $threadId;

    public function __construct(string $assistantId)
    {
        $this->client = OpenAI::client(config('services.openai.api_key'));
        $this->assistant = $this->client->assistants()->retrieve($assistantId);
    }

    public function create(array $config): static
    {
        $assistantResponse = $this->client->assistants()->create(array_merge_recursive([
            'model' => 'gpt-4-1106-preview',
            'name' => 'Jyu Assistant',
            'instructions' => 'You are a Jyu assistant. Please provide your feedback on the following prompt.',
            'tools' => [
                ['type' => 'retrieval'],
            ],
        ], $config));

        return new static($assistantResponse->id);
    }

    // TODO: $file should be array
    public function educate(string $file): static
    {
        $file = $this->client->files()->upload([
            'purpose' => 'assistants',
            'file' => fopen($file, 'rb'),
        ]);

        $this->client->assistants()->files()->create(
            $this->assistant->id,
            ['file_id' => $file->id]
        );

        return $this;
    }

    public function createThread(array $parameters = []): static
    {
        $threadResponse = $this->client->threads()->create($parameters);

        $this->threadId = $threadResponse->id;

        return $this;
    }

    public function messages(): OpenAI\Responses\Threads\Messages\ThreadMessageListResponse
    {
        // Fetch the messages from the run
        return $this->client->threads()->messages()->list($this->threadId);
    }

    public function write(string $message): static
    {
        $this->client->threads()->messages()->create(
            $this->threadId,
            ['role' => 'user', 'content' => $message],
        );

        return $this;
    }

    public function send(): OpenAI\Responses\Threads\Messages\ThreadMessageListResponse
    {
        $run = $this->client->threads()->runs()->create(
            $this->threadId,
            ['assistant_id' => $this->assistant->id]
        );

        do {
            sleep(1); // polling for the run status

            $run = $this->client->threads()->runs()->retrieve(
                threadId: $run->threadId,
                runId: $run->id
            );
        } while ($run->status !== 'completed');

        return $this->messages();
    }
}
