<?php

namespace App\Console\Commands;

use App\AI\Assistant;
use Illuminate\Console\Command;

use function Laravel\Prompts\info;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;

class ChatCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chat {--system=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start a chat with OpenAI';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $assistant = new Assistant();

        if ($this->option('system')) {
            $assistant->systemMessage($this->option('system'));
        }

        $question = text(
            label: 'What is your question for AI?',
            required: true
        );

        $response = spin(fn (): ?string => $assistant->send($question, false), 'Sending Request...');

        $this->info($response);

        while ($question = text('Do you want to respond?')) {
            $response = spin(fn (): ?string => $assistant->send($question, false), 'Sending Request...');

            info($response);
        }

        info('Conversation Over.');
    }
}
