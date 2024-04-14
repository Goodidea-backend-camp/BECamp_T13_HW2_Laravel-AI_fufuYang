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
