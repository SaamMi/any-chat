<?php

namespace SaamMi\AnyChat\Livewire;

use Illuminate\Support\Facades\Crypt;
use Livewire\Attributes\Validate;
use Livewire\Component;
use SaamMi\AnyChat\Events\NewMessage;
use SaamMi\AnyChat\Events\UserSentMessage;

class Publicchat extends Component
{
    #[Validate('required|string|max:1000|min:4')]
    public $message;

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

    public function sendMessage()
    {
        dd($this->validate(['message' => 'required|string|max:1000|min:4']));

        // 2. Sanitize Input for Security
        $cleanMessage = strip_tags(trim($this->message));

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
            'message' => $this->message,
            'chatId' => $this->activeChatId,
            'time' => now()->format('H:i'),
        ]))->toOthers();

        UserSentMessage::dispatch([
            'message' => $this->message,
            'chatId' => $this->activeChatId,

        ]);

        $this->reset('message');
    }

    public function render()
    {
        return view('anychat::livewire.test-chat');
    }
}
