<?php

use App\AI\Chat;
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

    $mp3 = (new Chat())->send(
        message: $prompt,
        speech: true
    );

    $file = "/roasts/" . md5($mp3) . ".mp3";

    file_put_contents(public_path($file), $mp3);

    return redirect('/')->with([
        'file' => $file,
    ]);
});

