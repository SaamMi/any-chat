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
use Livewire\Attributes\Computed;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;


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
    //public array $rows = [];
    public $searchableUsers;
    //public array $selectedUsers = [];
    public $rows = [['user_id' => null, 'name' => '', 'role' => 'member']];
    public $name;


   
   

    // Livewire automatically injects the route parameters and defaults here
    public function mount($config = [], User $user)
    {
        $this->config = $config;
        $this->path = '/' . ($config['id'] ?? 'anychat');
        //$this->initialChatId = $chatId;
        //$this->initialChatType = $type;
        $this->persistenceMode = $config['persistenceMode'] ?? 'stateless';
        
      // $user = Auth::user();
     /*  $this->rows = $user->currentTeam->members()->get()->map(function (User $user) {
            return [
                'user_id' => $user->id,
                'name' => $user->name,
                //'role' => $user->pivot->role ?? null,
            ];
        })->toArray();   */

    //$this->searchableUsers = $user->currentTeam->members()->get()->toArray();

     //dd($this->rows);
    }




public function searchAvailableUsers($searchQuery)
{
    if (strlen($searchQuery) < 2) {
        return [];
    }

    $user = \Illuminate\Support\Facades\Auth::user();

     $admin = \Illuminate\Support\Facades\Auth::user();


    return $user->currentTeam->members()
        ->where('name', 'like', '%' . $searchQuery . '%')
        ->take(10)
     
        ->get()
        ->map(function($member) {
            return [
                'id' => $member->id,
                'name' => $member->name,
            ];
        })
        ->reject(fn($u) => $u['id'] === $admin->id)
        ->values() // <-- CRUCIAL: Forces 0-indexing so it encodes as a true JS array []
        ->toArray();
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


     public function selectGroup($id, $type = \App\Models\User::class)
    {

     $admin = Auth::user();

     if (preg_match('/Group(\d+)/i', $id, $matches)) {
    $originalId = $matches[1]; 
}
    

         $conversationId = DB::table('groups')->where('id', $originalId)->value('conversation_id');
         
   //dd($conversationId);
           $this->receiver = $type::find($originalId);
        $this->activeConversation = $admin->getGroupConversationWith($originalId, $type, $conversationId);

     //   dd($this->activeConversation);
         $this->authParticipant = $this->activeConversation->participants()
            ->where('participantable_id', $admin->id)
            ->where('participantable_type', get_class($admin))
            ->first();

            return $this->getHistory();
      
    }
      public function sendMessage($text)
    {
        if (!$this->activeConversation || !$this->authParticipant) return;

        $msg = $this->activeConversation->messages()->create([
            'body' => strip_tags(trim($text)),
            'participant_id' => $this->authParticipant->id,
            'type' => 'text',
        ]);

      $payload = [
            'message' => $msg->body,
            'senderName' => $msg->participant->participantable->name ?? 'User',
            'chatId'  => $this->receiver->id, 
            'auth'    => 0, 
            'time' => now()->format('g:i A'),
        ];

          broadcast(new NewMessage($payload))->toOthers();

        $this->reset('message');
    }


   
public function save()
{
    $conversation = Conversation::create(['type' => 'group']);
     
    DB::table('groups')->insert([
        'name' => $this->name,
        'conversation_id' => $conversation->id
    ]);

    $admin = Auth::user();

    $conversation->participants()->create([
        'participantable_id' => $admin->id, 
        'participantable_type' => get_class($admin), 
        'role' => 'owner'
    ]);
   

     $admin->currentTeam->groupMemberships()->create([
            'group_name' => $this->name,
            'user_id'    => $admin->id,
            'role'       => 'admin'
        ]);
    foreach ($this->rows as $row) {
        // Skip the empty placeholder row if no users were selected
        if(empty($row['user_id'])) continue; 

        // Pass a standard associative array; team_id is injected automatically
        $admin->currentTeam->groupMemberships()->create([
            'group_name' => $this->name,
            'user_id'    => $row['user_id'],
            'role'       => $row['role']
        ]);
    }
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

  
    public function toggleUser($userId, $name, $role)
{
    // Search to see if the user has already been added to the rows array
    $index = collect($this->rows)->search(function ($row) use ($userId) {
        return isset($row['user_id']) && $row['user_id'] == $userId;
    });

    if ($index !== false) {
        // If they exist, the box was unchecked. Remove them.
        unset($this->rows[$index]);
        $this->rows = array_values($this->rows); // Re-index the array
    } else {
        // If they don't exist, the box was checked. Add them.
        // Check if the very first row is just an empty placeholder and replace it
        if (count($this->rows) === 1 && empty($this->rows[0]['user_id'])) {
            $this->rows[0] = ['user_id' => $userId, 'name' => $name, 'role' => $role];
        } else {
            // Otherwise, append a new user row
            $this->rows[] = ['user_id' => $userId, 'name' => $name, 'role' => $role];
        }
    }
}

public function updateRole($userId, $role)
{
    // Find the specific user and update their role
    $index = collect($this->rows)->search(function ($row) use ($userId) {
        return isset($row['user_id']) && $row['user_id'] == $userId;
    });

    if ($index !== false) {
        $this->rows[$index]['role'] = $role;
    }
}



   
  

  

      #[Computed]
    public function availableRoles(): array
    {
        return TeamRole::assignable();
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

         $groupUsers = DB::table('groups')
                        ->get()
                        ->map(function ($group) {
                            return [
                                'id' => $group->id,
                                'name' => $group->name,
                                'type' => '\App\Models\User'
                            ];
                        })
                        ->toArray();

        $view = view('anychat::livewire.publicresponse', [
            'users'              => \App\Models\User::all(),
            'guestConversations' => $guestConversations,
            'group'              => $groupUsers,
            
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