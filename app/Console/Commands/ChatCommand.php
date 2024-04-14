<?php

namespace App\Console\Commands;

use App\AI\Assistant;
use Illuminate\Console\Command;
use function Laravel\Prompts\{text, info, spin};

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
    public function handle()
    {
        $chat = new Assistant();

        if ($this->option('system')){
            $chat->systemMessage($this->option('system'));
        }

        $question = text(
            label: 'What is your question for AI?',
            required: true
        );

        $response = spin(fn() => $chat->send($question), 'Sending Request...');

        $this->info($response);

        while($question = text('Do you want to respond?')){
            $response = spin(fn() => $chat->send($question), 'Sending Request...');

            info($response);
        }

        info('Conversation Over.');
    }
}
