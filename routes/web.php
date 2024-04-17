<?php

use App\AI\Assistant;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    //    session()->forget('file');

    return view('roast');
});

Route::post('/roast', function () {
    $attributes = request()->validate([
        'topic' => ['required', 'string', 'min:2', 'max:50'],
    ]);

    $prompt = "Please roast {$attributes['topic']} in a sarcastic tone.";

    $mp3 = (new Assistant())->send(
        message: $prompt,
        speech: true
    );

    $file = '/roasts/'.md5($mp3).'.mp3';

    file_put_contents(public_path($file), $mp3);

    return redirect('/')->with([
        'file' => $file,
    ]);
});

Route::get('/image', function () {
    return view('image', [
        'messages' => session('messages', []),
    ]);
});

Route::post('/image', function () {
    $attributes = request()->validate([
        'description' => ['required', 'string', 'min:3'],
    ]);

    $assistant = new App\AI\Assistant(session('messages', []));

    $assistant->visualize($attributes['description']);

    session(['messages' => $assistant->messages()]);

    return redirect('/image');
});

Route::get('/replies', function () {
    return view('create-reply');
});

Route::post('/replies', function () {
    request()->validate([
        'body' => [
            'required',
            'string',
            function ($attribute, $value, $fail) {
                $assistant = new Assistant();

                $response = $assistant->client->chat()->create([
                    'model' => 'gpt-3.5-turbo-1106',
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a forum moderator who always responds using JSON.'],
                        [
                            'role' => 'user',
                            'content' => <<<EOT
                    Please inspect the following text and determine if it is spam.

                    { $value }
                    
                    Expected Response Example:

                    {"is_spam": true|false}
                    EOT
                        ],
                    ],
                    'response_format' => ['type' => 'json_object'],
                ])->choices[0]->message->content;

                $response = json_decode($response);

                if ($response->is_spam) {
                    $fail('Spam was detected.');
                }
            },
        ],
    ]);

    return 'Post is valid.';
});

Route::get('/assistant', function () {
    $assistantObject = new Assistant();

    $file = $assistantObject->client->files()->upload([
        'purpose' => 'assistants',
        'file' => fopen(storage_path('docs/jyu.md'), 'rb'),
    ]);

    $assistant = $assistantObject->client->assistants()->create([
        'model' => 'gpt-4-1106-preview',
        'name' => 'Jyu Assistant',
        'instructions' => 'You are a Jyu assistant. Please provide your feedback on the following prompt.',
        'tools' => [
            ['type' => 'retrieval'],
        ],
        'file_ids' => [
            $file->id,
        ],
    ]);

    $run = $assistantObject->client->threads()->createAndRun([
        'assistant_id' => $assistant->id,
        'thread' => [
            'messages' => [
                ['role' => 'user', 'content' => 'Who is JYu?']
            ]
        ]
    ]);

    do {
        sleep(1); // polling for the run status

        $run = $assistantObject->client->threads()->runs()->retrieve(
            threadId: $run->threadId,
            runId: $run->id
        );
    } while ($run->status !== 'completed');

    // Fetch the messages from the run
    $messages = $assistantObject->client->threads()->messages()->list($run->threadId);

    dd($messages);
});
