<?php

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Mail\Message;

Route::get('/', function () {
    return view('welcome');
});


Route::Get('/test-mail', function () {
    Mail::raw('Hello world', function (Message $message) {
        $message->to('ta.lhc5922@gmail.com');
    });
});

// Include business routes with prefix
Route::prefix('business')->group(base_path('routes/business.php'));
