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

    $prompt = sprintf('Please roast %s in a sarcastic tone.', $attributes['topic']);

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

Route::post('/replies', function (): string {
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

                    { {$value} }
                    
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

/**
 * @note 這個 Route 還在測試階段，請不要使用或更動
 */
Route::get('/assistant', function (): never {
    $assistant = new \App\AI\LaraparseAssistant(config('services.openai.assistant_id'));

    $messages = $assistant->createThread()
        ->write('Hello.')
        ->write('Do you know who is JYu?')
        ->send();

    dd($messages);
});
