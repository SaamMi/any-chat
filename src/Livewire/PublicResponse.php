<?php

namespace SaamMi\AnyChat\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use SaamMi\AnyChat\Events\NewMessage;
use SaamMi\AnyChat\Models\Participant;
use SaamMi\AnyChat\Models\Conversation;
use SaamMi\AnyChat\Traits\InteractsWithConversations;
use App\Models\User;
use Livewire\Attributes\Layout;
use SaamMi\AnyChat\Contracts\AiCopilot;

class PublicResponse extends Component
{
    use InteractsWithConversations;
    
    public $path;
    public $config;
    public $activeConversation; 
    public $receiver;            
    public $authParticipant;     
    public $message = '';

    public $searchQuery = '';
    // Properties to catch the URL parameters
    public $initialChatId;
    public $initialChatType;

    // Livewire automatically injects the route parameters and defaults here
    public function mount($config = [], $chatId = null, $type = null)
    {
        $this->config = $config;
        $this->path = '/' . ($config['id'] ?? 'anychat');
        $this->initialChatId = $chatId;
        $this->initialChatType = $type;
    }


  

public function generateAiReply($chatId)
{
    // 1. Fetch recent history and format it for AI consumption
    $recentMessages = \SaamMi\AnyChat\Models\Message::where('conversation_id', $chatId)
        ->orderBy('created_at', 'desc')
        ->take(6)
        ->get()
        ->reverse()
        ->map(function ($msg) {
            return [
                // Assuming 'auth' == 1 means admin/agent
                'role' => $msg->auth == 1 ? 'assistant' : 'user', 
                'content' => $msg->body ?? $msg->message,
            ];
        })
        ->toArray();

    if (empty($recentMessages)) {
        return "No context available.";
    }

    // 2. Resolve the AI Provider from Laravel's Container
    $copilot = app(AiCopilot::class);

    // 3. Generate and return the reply
    return $copilot->generateReply($recentMessages);
}
   
    public function selectUser($id, $type = User::class)
    {
        $admin = Auth::user();
        $this->receiver = $type::find($id);
        $this->activeConversation = $admin->getDirectConversationWith($id, $type);
        $this->authParticipant = $this->activeConversation->participants()
            ->where('participantable_id', $admin->id)
            ->where('participantable_type', get_class($admin))
            ->first();

        return $this->getHistory();
    }




public function performSearch($query)
{
    // Return empty if search term is too short
    if (strlen($query) < 3) return [];

    // Search for messages matching the query
    return \SaamMi\AnyChat\Models\Message::where('body', 'like', '%' . $query . '%')
        ->with(['participant.participantable'])
        ->latest()
        ->get()
        ->map(function($msg) {
            return [
                'id' => $msg->id,
                'body' => $msg->body,
                'sender' => $msg->participant->participantable->name ?? 'User',
                // Map the IDs needed for jumpToMessage logic
                'chatId' => $msg->participant->participantable_id,
                'chatType' => $msg->participant->participantable_type,
            ];
        })->toArray();
}
    public function sendMessage($text)
    {
        if (!$this->activeConversation || !$this->authParticipant) return;

        $msg = $this->activeConversation->messages()->create([
            'body' => strip_tags(trim($text)),
            'participant_id' => $this->authParticipant->id,
            'type' => 'text',
        ]);

        NewMessage::dispatch([
            'message' => $msg->body,
            'chatId'  => $this->receiver->id, 
            'auth'    => 1, 
            'time'    => now()->format('H:i'),
        ]);

        $this->reset('message');
    }

    public function getHistory()
    {
        if (!$this->activeConversation) return [];
        return $this->activeConversation->messages()
            ->oldest()
            ->with('participant.participantable')
            ->get()
            ->map(fn($msg) => [
                'id' => $msg->id,
                'message'    => $msg->body,
                'auth'       => $msg->participant_id === $this->authParticipant->id ? 1 : 0,
                'senderName' => $msg->participant->participantable->name ?? 'User',
            ]);
    }

    
    public function render()
    {
        return view('anychat::livewire.publicresponse', [
            'users' => \App\Models\User::all(),
        ])->layout('anychat::panel-master', [
        // This explicitly passes the component's config to your layout file!
        'config' => $this->config 
    ]);
    }
}