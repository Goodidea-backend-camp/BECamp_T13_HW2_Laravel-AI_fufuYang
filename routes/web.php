<?php

use App\AI\Chat;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $chat = new Chat();

    $poem = $chat
        ->systemMessage("You are a poetic assistant, skilled in explaining complex programming concepts with creative flair.")
        ->send("Compose a poem that compliment my girlfriend that she is incredibly beautiful.");

    $sillyPoem = $chat->reply('Cool, can you make it much, much more sillier?');

    return view('welcome', [
        'poem' => $sillyPoem
    ]);
});
