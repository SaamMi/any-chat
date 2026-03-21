<?php

namespace SaamMi\AnyChat\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(array $message)
    {
        // $message['chatId'] is the unique ID from your stateless token
        $this->message = $message;
    }

    public function broadcastOn(): array
    {
        // Each chat gets its own private-like channel based on the token ID
        return [
            new Channel('chat.'.$this->message['chatId']),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.new';
    }
}
