<?php

namespace SaamMi\AnyChat\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserSentMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(array $message)
    {

        $this->message = $message;

    }

    public function broadcastOn(): array
    {
        // All agent dashboards listen here
        return [new Channel('anychat-support')];
    }

    public function broadcastAs(): string
    {
        // This matches the .listen('.user.sent') in Javascript
        return 'user.sent';
    }
}
