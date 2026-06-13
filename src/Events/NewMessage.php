<?php

namespace SaamMi\AnyChat\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
// 1. Change this import
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// 2. Implement ShouldBroadcastNow instead of ShouldBroadcast
class NewMessage implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(array $message)
    {
        $this->message = $message;
    }

    public function broadcastOn(): array
    {
        // 3. Safely fallback to participantable_id if chatId is missing
        $channelId = $this->message['chatId'] 
                  ?? $this->message['participantable_id'] 
                  ?? 'default-fallback';

        return [
            new Channel('chat.' . $channelId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.new';
    }
}