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
    public $allowUploads = false;
    public $allowEmojis = false;
    public $attachment;
    public $config;
    public $senderName = 'Guest'; // Default identity

    #[Validate('required|string|max:1000|min:2')]
    public $message;

    protected $sender; 
    public $participantable_id;
    public $conversation_id;

    public function mount($config)
    {
        $this->config = $config;
        $this->allowUploads = $config['uploads'] ?? false;
        $this->persistenceMode = $config['persistenceMode'] ?? 'stateless';
        $this->allowEmojis = $config['emojis'] ?? false;
    }

    public function booted()
    {
        // 1. Identify Identity: Auth User takes priority over Guest Token[cite: 4]
        if (Auth::check()) {
            $this->sender = Auth::user();
            $this->participantable_id = $this->sender->getAuthIdentifier();
            $this->senderName = $this->sender->name; 
            return;
        }

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

    public function sendMessage()
    {
        $this->validate();

        if (!$this->sender) {
            $this->performGuestHandshake();
        }

        if ($this->persistenceMode === 'stateful') {
            $this->handleStatefulStorage();
        } else {
            $this->handleStatelessBroadcast();
        }

        $this->reset('message');
    }
protected function performGuestHandshake()
{
    if (!$this->participantable_id) {
        $this->participantable_id = bin2hex(random_bytes(16));
        $this->conversation_id = $this->participantable_id; 

        // 1. Actually create the sender model in the database
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

    protected function handleStatefulStorage()
    {
        $targetId = $this->config['target_id'] ?? 1;
        $targetType = $this->config['target_type'] ?? 'App\Models\User';

        $conversation = $this->sender->getDirectConversationWith($targetId, $targetType);

        $participant = Participant::where([
            'conversation_id' => $conversation->id,
            'participantable_id' => $this->participantable_id,
            'participantable_type' => get_class($this->sender),
        ])->first();

        //dd($participant);

        $savedMessage = $conversation->messages()->create([
            'body' => strip_tags(trim($this->message)),
            'participant_id' => $participant->id,
            'type' => 'text',
        ]);

        if ($this->attachment && $this->allowUploads) {
            $this->processAttachment($savedMessage);
        }

        $this->broadcastMessage($conversation->id);
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

    protected function handleStatelessBroadcast()
    {
        $this->broadcastMessage($this->conversation_id);
    }

    protected function broadcastMessage($conversationId)
    {
        $payload = [
            'message' => strip_tags(trim($this->message)),
            'conversation_id' => $conversationId,
            'participantable_id' => $this->participantable_id,
            'time' => now()->format('H:i'),
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
        return view('anychat::livewire.test-chat');
    }
}