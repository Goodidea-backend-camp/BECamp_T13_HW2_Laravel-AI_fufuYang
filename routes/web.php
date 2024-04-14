<?php

use App\AI\Assistant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
//    session()->forget('file');

    return view('roast');
});


Route::post('/roast', function () {
    $attributes = request()->validate([
        'topic' => ['required', 'string', 'min:2', 'max:50']
    ]);

    $prompt = "Please roast {$attributes['topic']} in a sarcastic tone.";

    $mp3 = (new Assistant())->send(
        message: $prompt,
        speech: true
    );

    $file = "/roasts/" . md5($mp3) . ".mp3";

    file_put_contents(public_path($file), $mp3);

    return redirect('/')->with([
        'file' => $file,
    ]);
});

Route::get('/image', function () {
    return view('image', [
        'messages' => session('messages', [])
    ]);
});


Route::post('/image', function () {
    $attributes = request()->validate([
        'description' => ['required', 'string', 'min:3']
    ]);

    $assistant = new App\AI\Assistant(session('messages', []));

   $assistant->visualize($attributes['description']);

    session(['messages' => $assistant->messages()]);

    return redirect('/image');
});

Route::get('/replies', function (){
    return view('create-reply');
});

Route::post('/replies', function(){
    $attributes = request()->validate([
       'body' => ['required', 'string']
    ]);


    $assistant = new Assistant();

    $response = $assistant->client->chat()->create([
        'model' => 'gpt-3.5-turbo-1106',
        'messages' => [
            ['role' => 'system', 'content' => 'You are a forum moderator who always responds using JSON.'],
            [
                'role' => 'user',
                'content' => <<<EOT
                    Please inspect the following text and determine if it is spam.

                    {$attributes['body']}

                    Expected Response Example:

                    {"is_spam": true|false}
                    EOT
            ]
        ],
        'response_format' => ['type' => 'json_object']
    ])->choices[0]->message->content;

    $response = json_decode($response);

    // Trigger failed validation, display a flash message, abort...
    return $response->is_spam ? 'THIS IS SPAM!': 'VALID POST';

});
