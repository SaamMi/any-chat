<?php

namespace SaamMi\AnyChat\Livewire;

use Livewire\Component;
use SaamMi\AnyChat\Events\NewMessage;

class PublicResponse extends Component
{
    public $message = '';

    /**
     * $chatId and $message are passed directly from Alpine:
     * this.$wire.sendMessage(this.activeChatId, this.message)
     */
    public function sendMessage($chatId, $message)
    {
        // 1. Dispatch the event to the USER'S unique channel
        // Ensure NewMessage broadcasts on 'chat.' . $chatId
        NewMessage::dispatch([
            'message' => $message,
            'chatId' => $chatId,
            'time' => now()->format('H:i'),
            'auth' => 1, // 1 = Admin/Support
        ]);
    }

    public function render()
    {
        return view('anychat::livewire.publicresponse');
    }
}
