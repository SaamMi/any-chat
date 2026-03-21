<?php

use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Route;
use SaamMi\AnyChat\Http\Controllers\FetchMessage;
use SaamMi\AnyChat\Http\Middleware\CustomCookie;
use SaamMi\AnyChat\Livewire\Publicchat;
use SaamMi\AnyChat\Livewire\PublicResponse;
use SaamMi\AnyChat\Models\Message;

Route::get('/welcome', function () {
    return view('welcome');
})->name('home');

/*Route::get('/chat', function () {
    return view('livewire.chat');
})
->middleware(CustomCookie::class)
->name('chat'); */

Route::get('/chat', Publicchat::class)
    ->middleware(CustomCookie::class)
    ->name('chat');

// Route::middleware(['auth', 'verified'])->group(function () {
/*Route::get('/',Publicchat::class)
->middleware(CustomCookie::class); */

// });

Route::get('/chatresponse', PublicResponse::class);

Route::get('/fetch-messages', [Publicchat::class, 'msg'])
    ->name('fetch-messages');

Route::get('/fetch-cookie', [PublicResponse::class, 'distinctcookie'])
    ->name('fetch-cookie');

Route::get('/fetch-message', function () {
    return Message::all();

})
    ->name('fetch-message');

Route::get('/fm', FetchMessage::class)
    ->name('fm');

/* Cookie::queue('my_cookie','my_value',60);

 return view('livewire.publicchat');
});   */

Route::get('/set-cookie', function () {

    $cookie = cookie('my_cookie', 'John Doe', 60);

    return response('Cookie set')->cookie($cookie);

});

Route::get('/get-cookie', function () {

    $userName = request()->cookie('ccookie');

    return "User Name: $userName";

});

Route::get('/welcome', function () {
    return view('welcome');
});
