<?php

namespace SaamMi\AnyChat\Livewire;

use Illuminate\Support\Facades\Crypt;
use Livewire\Component;
use SaamMi\AnyChat\Events\NewMessage;
use SaamMi\AnyChat\Events\UserSentMessage;

class Publicchat extends Component
{
    protected $activeChatId;

    /**
     * This runs before any Livewire action (like sendMessage).
     * It handles the stateless "authentication" internally.
     */
    public function booted()
    {
        $token = request()->header('X-AnyChat-Token');

        if ($token) {
            try {
                $payload = json_decode(Crypt::decryptString($token), true);
                $this->activeChatId = $payload['chatId'];
            } catch (\Exception $e) {
                $this->activeChatId = null;
            }
        }
    }

    public function sendMessage($messageText)
    {
        if (empty($messageText)) {
            return;
        }

        // Handshake: If no activeChatId, generate a new identity
        if (!$this->activeChatId) {
            $this->activeChatId = bin2hex(random_bytes(16));
            $token = Crypt::encryptString(json_encode([
                'chatId' => $this->activeChatId,
                'exp' => now()->addDays(7)->timestamp,
            ]));

            // Tell Alpine to store this for all future requests
            $this->dispatch('token-handshake', token: $token, chatId: $this->activeChatId);
        }

        // Relay to Reverb/Pusher
        broadcast(new NewMessage([
            'message' => $messageText,
            'chatId' => $this->activeChatId,
            'time' => now()->format('H:i'),
        ]))->toOthers();

        UserSentMessage::dispatch([
            'message' => $messageText,
            'chatId' => $this->activeChatId,

        ]);
    }

    public function render()
    {
        return view('anychat::livewire.test-chat');
    }
}
