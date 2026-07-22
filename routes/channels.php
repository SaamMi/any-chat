<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat.{channelId}', function (User $user, $channelId) {
    
   
   // return (int) $user->id === (int) $channelId;
   return true;
    
});