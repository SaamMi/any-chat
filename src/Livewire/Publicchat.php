<?php

namespace SaamMi\AnyChat\Livewire;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Validate;
use SaamMi\AnyChat\Events\NewMessage;
use SaamMi\AnyChat\Events\UserSentMessage;
use SaamMi\AnyChat\Models\Guest;
use SaamMi\AnyChat\Models\Participant;

class Publicchat extends Component
{
    use WithFileUploads;

    public $persistenceMode = 'stateless';
    public $allowUploads = true;
    public $allowEmojis = false;
    public $attachment;
    public $config;
    public $senderName = 'Guest'; 

    #[Validate('required|string|max:1000|min:2')]
    public $message;

    protected $sender; 
    public $participantable_id;
    public $conversation_id;

    public function mount($config = [])
    {
        $this->config = $config;
        $this->persistenceMode = $config['persistenceMode'] ?? 'stateless';
        $this->allowEmojis = $config['emojis'] ?? false;
    }

    public function booted()
    {
        // 1. Identify Identity: Auth User takes priority over Guest Token
        if (Auth::check()) {
            $this->sender = Auth::user();
            $this->participantable_id = $this->sender->getAuthIdentifier();
            $this->senderName = $this->sender->name; 
            return;
        }

        // Try to hydrate from header (mostly works on initial load)
        $token = request()->header('X-AnyChat-Token');
        if ($token) {
            try {
                $payload = json_decode(Crypt::decryptString($token), true);
                $this->participantable_id = $payload['participantable_id'];
                $this->conversation_id = $payload['conversation_id'] ?? null;
                $this->sender = Guest::firstOrCreate(['id' => $this->participantable_id]);
                $this->senderName = 'Guest ' . substr($this->participantable_id, 0, 4);
            } catch (\Exception $e) {
                $this->sender = null;
            }
        }
    }

    // Accept the explicitly passed variables from the Alpine frontend
    public function sendMessage($text = null, $guestId = null)
    {
        // Hydrate Guest identity if Livewire dropped the state on this specific request
        if (!Auth::check() && $guestId && !$this->sender) {
            $this->participantable_id = $guestId;
            $this->sender = Guest::find($guestId);
            if ($this->sender) {
                $this->senderName = 'Guest ' . substr($guestId, 0, 4);
            }
        }

        // If it's a completely new visitor, perform handshake to generate an ID
        if (!$this->sender) {
            $this->performGuestHandshake();
        }

        if ($this->persistenceMode === 'stateful') {
            $this->handleStatefulStorage($text);
        } else {
            $this->handleStatelessBroadcast($text);
        }

        $this->reset('message');
    }

    protected function performGuestHandshake()
    {
        if (!$this->participantable_id) {
            $this->participantable_id = bin2hex(random_bytes(16));
            $this->conversation_id = $this->participantable_id; 

            // 1. Create the sender model in the database
            $this->sender = Guest::create([
                'id' => $this->participantable_id
            ]);

            // 2. Set the display name immediately
            $this->senderName = 'Guest ' . substr($this->participantable_id, 0, 4);

            $token = Crypt::encryptString(json_encode([
                'participantable_id' => $this->participantable_id,
                'participantable_type' => 'Guest',
                'conversation_id' => $this->conversation_id,
                'exp' => now()->addDays(7)->timestamp,
            ]));

            $this->dispatch('token-handshake', 
                token: $token, 
                chatId: $this->participantable_id 
            );
        }
    }

    protected function handleStatefulStorage($text)
    {
        $targetId = $this->config['target_id'] ?? 1;
        $targetType = $this->config['target_type'] ?? 'App\Models\User';

        // $this->sender is now guaranteed to exist, safely call trait method
        $conversation = $this->sender->getDirectConversationWith($targetId, $targetType);

        $participant = Participant::where([
            'conversation_id' => $conversation->id,
            'participantable_id' => $this->participantable_id,
            'participantable_type' => get_class($this->sender),
        ])->first();

        // Prioritize the raw text passed from the Alpine function
        $messageText = strip_tags(trim($text ?? $this->message));

        $savedMessage = $conversation->messages()->create([
            'body' => $messageText,
            'participant_id' => $participant->id,
            'type' => 'text',
        ]);

        if ($this->attachment && $this->allowUploads) {
            $this->processAttachment($savedMessage);
        }

        $this->broadcastMessage($conversation->id, $messageText);
    }

    protected function processAttachment($message)
    {
        $path = $this->attachment->store('chat-attachments', 'public');
        $message->attachments()->create([
            'file_path'     => $path,
            'file_name'     => $this->attachment->hashName(),
            'original_name' => $this->attachment->getClientOriginalName(),
            'url'           => \Storage::url($path),
            'mime_type'     => $this->attachment->getMimeType(),
        ]);
        $this->attachment = null;
    }

    protected function handleStatelessBroadcast($text)
    {
        $messageText = strip_tags(trim($text ?? $this->message));
        
        // Broadcast directly to the guest's unique channel ID
        $this->broadcastMessage($this->conversation_id ?? $this->participantable_id, $messageText);
    }

    protected function broadcastMessage($conversationId, $text)
    {
        $payload = [
            'message' => $text,
            'conversation_id' => $conversationId,
            'participantable_id' => $this->participantable_id,
            'time' => now()->format('g:i A'),
        ];

        broadcast(new NewMessage($payload))->toOthers();
        broadcast(new UserSentMessage([
            'message' => $payload['message'],
            'chatId'  => $this->participantable_id,
            'time'    => $payload['time'],
            'senderName' => $this->senderName,
        ]));
    }

    public function render()
    {
        return view('anychat::livewire.test-chat')->layout('anychat::panel-master', [
            'config' => $this->config 
        ]);
    }
}