<?php

namespace SaamMi\AnyChat\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;


class NewMessage implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(array $message)
    {
        $this->message = $message;
        //dd($this->message);
    }

    public function broadcastOn(): array
    {
      

          $channelId = $this->message['chatId'] 
                  ?? $this->message['participantable_id'] 
                  ?? 'default-fallback';


      

        return [
            new PrivateChannel('chat.'.$channelId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.new';
    }
}