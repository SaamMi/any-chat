<?php

use App\Broadcasting\ChatChannel;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('messages0bQR7tXx45xdrlf7', function () {
    return true;
});

Broadcast::channel('chat-room', ChatChannel::class);
