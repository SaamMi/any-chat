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
use App\Models\Team;
use App\Enums\TeamRole;


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
    public array $rows = [];

    // Livewire automatically injects the route parameters and defaults here
    public function mount($config = [], $chatId = null, $type = null)
    {
        $this->config = $config;
        $this->path = '/' . ($config['id'] ?? 'anychat');
        $this->initialChatId = $chatId;
        $this->initialChatType = $type;
        $this->persistenceMode = $config['persistenceMode'] ?? 'stateless';

       /* $this->rows = $this->team->users()->get()->map(function (User $user) {
            return [
                'user_id' => $user->id,
                'role' => $user->pivot->role ?? null,
            ];
        })->toArray();    */ 
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
        if (strlen($query) < 1) return [];

        $admin = \Illuminate\Support\Facades\Auth::user();

        // Search for messages matching the query, loading the conversation participants
        return \SaamMi\AnyChat\Models\Message::where('body', 'like', '%' . $query . '%')
            ->with(['participant.participantable', 'conversation.participants'])
            ->latest()
            ->get()
              ->filter(function ($msg) use ($admin) {
                    // Filter for conversations containing an auth participant
                    return $msg->conversation->participants->contains(fn($p) => $p->participantable_id == $admin->id);
                })
            ->map(function($msg) use ($admin) {
                
                // 1. Find the Chat Partner (The participant who is NOT the admin)
                $partner = $msg->conversation->participants->first(function($p) use ($admin) {
                    return !($p->participantable_id == $admin->id && $p->participantable_type == get_class($admin));
                });

                return [
                    'id' => $msg->id,
                    'body' => $msg->body,
                    'sender' => $msg->participant->participantable->name ?? 'User',
                    
                    // 2. CRITICAL FIX: Assign the result to the Partner's ID so the sidebar highlights the correct chat
                    'chatId' => $partner ? $partner->participantable_id : $msg->participant->participantable_id,
                    'chatType' => $partner ? $partner->participantable_type : $msg->participant->participantable_type,
                ];
            })->toArray();
    }

    public function performUserSearch($query)
    {


    
     
   return \App\Models\User::where('name', 'like', '%' . $query . '%')->get()->toArray();



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
            'time' => now()->format('g:i A'),
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
                'time'       => $msg->created_at->format('g:i A'),
            ]);
    }

    
  public function render()
    {
        $guestConversations = [];

        // Check if the conversation model exists before querying
        if (class_exists('\SaamMi\AnyChat\Models\Conversation')) {
            $guestConversations = \SaamMi\AnyChat\Models\Conversation::with(['participants.participantable'])
                ->latest('updated_at')
                ->get()
                ->filter(function ($conv) {
                    // Filter for conversations containing a guest participant
                    return $conv->participants->contains(fn($p) => $p->participantable_type !== \App\Models\User::class);
                })
                ->map(function ($conv) {
                    // Locate the guest participant in this conversation
                    $guest = $conv->participants->first(fn($p) => $p->participantable_type !== \App\Models\User::class);
                    
                    return [
                        'id'   => $guest->participantable_id, // <-- CRITICAL FIX: Pass the Guest Model ID, NOT $conv->id
                        'name' => $guest->participantable->name ?? 'Guest #' . substr($guest->participantable_id, 0, 6),
                        'type' => $guest->participantable_type ?? 'SaamMi\AnyChat\Models\Guest',
                    ];
                })
                ->unique('id') // Prevent duplicate sidebar rows for the same guest
                ->values()
                ->toArray();
        }

        $view = view('anychat::livewire.publicresponse', [
            'users'              => \App\Models\User::all(),
            'guestConversations' => $guestConversations,
            
        ]);

        // Only explicitly set the layout for the standalone route
        if (!request()->routeIs('chatresponse')) {
            // Use your package's namespace to load the bundled clean layout
            $view->layout('anychat::panel-master', [
            'config' => $this->config 
        ]);
        }

        // If it's the internal route, Livewire will naturally fall back 
        // to the host app's default components.layouts.app
        return $view;
  
    }
}