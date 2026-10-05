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
use SaamMi\AnyChat\Models\Group;


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

     public $groupRows = [['user_id' => null, 'name' => '', 'role' => 'member', 'status' => '']];

    public $name;
    
    public array $members = [];


   
   

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
       /*->map(function($member) {
            return [
                'id' => $member->id,
                'name' => $member->name,
            ];
        }) */
       // ->reject(fn($u) => $u['id'] === $admin->id)
        ->values() // <-- CRUCIAL: Forces 0-indexing so it encodes as a true JS array []
        ->toArray();
}

public function searchAvailableTeamUsers($searchQuery,$editChatId)
{
    if (strlen($searchQuery) < 2) {
        return [];
    }
//dd($editChatId);
    $user = \Illuminate\Support\Facades\Auth::user();

    // 1. Fetch all team members in a single query
    $allTeamMembers = $user->currentTeam->members()->get();

    //dd($allTeamMembers);

    // 2. Fetch group member IDs for status determination
   // $groupMemberIds = $user->currentTeam->groupMembers()->get()->pluck('id')->toArray();

$currentGroup = Group::find($editChatId);

   $groupMemberIds = $currentGroup->groupMembers()->get()->pluck('id')->toArray();




//   dd($groupMemberIds);
    // 3. Process, partition, and sort
   return $allTeamMembers
        ->reject(fn($u) => $u->id === $user->id) // Exclude current user/admin
        ->map(function ($u) use ($searchQuery, $groupMemberIds) {
            // Check if user matches the search query (case-insensitive)
            $isSearchResult = stripos($u->name, $searchQuery) !== false;

            return [
                'id' => $u->id,
                'name' => $u->name,
                'status' => in_array($u->id, $groupMemberIds) ? 'member' : 'non-member',
                'is_match' => $isSearchResult ? 0 : 1, // 0 comes first when sorting asc
                'role' => $u->pivot->role,
            ];
        })
        ->sortBy([
            ['is_match', 'asc'], // Search query matches first, remaining team at the bottom
            ['id', 'asc'],       // Both groups sorted internally by ID
        ])
        ->map(function ($item) {
            unset($item['is_match']); // Remove the temporary sorting flag
            return $item;
        })
        ->values() // Re-indexes array starting from 0 for JS
        ->toArray();



      //  dd($result);
}

public function groupVisibility()
{
    $result = Auth::user()
    ->ownedTeams()
    ->with(['groups' => function ($query) {
        // Filter the belongsToMany relation directly via the pivot column
        $query->wherePivotIn('role', ['admin']); 
        
        // Note: If your role is an Enum, use the value property:
        // $query->wherePivotIn('role', [TeamRole::Owner->value, TeamRole::Admin->value]);
    }])
    ->get()
    ->mapWithKeys(function ($team) {
        // Map the results to your requested [team_id => [group_names]] format
        return [$team->id => $team->groups->pluck('name')->toArray()];
        
        // If you prefer group IDs instead of names, simply change 'name' to 'id'
    })
    ->toArray();
//dd($result);

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

    public function editGroup()
     {
     	
           $admin = Auth::user();
     	       $this->members = $admin->currentTeam->groupMembers()->get()->map(fn ($member) => [
            'id' => $member->id,
            'name' => $member->name,
            'email' => $member->email,
            'avatar' => $member->avatar ?? null,
            'initials' => $member->initials(),
            'role' => $member->pivot->role->value,
         //   'role_label' => $member->pivot->role->label(),
        ])->toArray();

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
     
   $group = Group::create([
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
            'group_id'   => $group->id,
            'user_id'    => $admin->id,
            'role'       => 'admin'
        ]);
    foreach ($this->rows as $row) {
        // Skip the empty placeholder row if no users were selected
        if(empty($row['user_id'])) continue; 

        // Pass a standard associative array; team_id is injected automatically
        $admin->currentTeam->groupMemberships()->create([
            'group_name' => $this->name,
            'group_id'   => $group->id,
            'user_id'    => $row['user_id'],
            'role'       => $row['role']
        ]);
    }
}
public function saveGroup($groupId,$groupName)
{
    $group = Group::find($groupId);

    if (!$group) {
        return;
    }

    foreach ($this->groupRows as $row) {
        // Skip empty placeholder
        if (empty($row['user_id'])) {
            continue;
        }

        if ($row['status'] === 'member') {
            // Attach user to group or update their role if they are already in the group
            DB::table('team_groups')->updateOrInsert(
                [
                    'group_id' => $groupId,
                    'group_name' => $groupName,
                    'user_id'  => $row['user_id'],
                    'team_id'   => Auth::user()->current_team_id,
                ],
                [
                    'role'       => $row['role'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        } elseif ($row['status'] === 'non-member') {
            // Remove user from the group
            DB::table('team_groups')
                ->where('group_id', $groupId)
                ->where('user_id', $row['user_id'])
                ->delete();
        }
    }

    // Reset the queue after successful save
    $this->groupRows = [['user_id' => null, 'name' => '', 'role' => 'member', 'status' => '']];
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

public function toggleGroupUser($userId, $name, $role, $status)
{
    // Search to see if the user has already been added to the pending queue
    $index = collect($this->groupRows)->search(function ($row) use ($userId) {
        return isset($row['user_id']) && $row['user_id'] == $userId;
    });

    if ($index !== false) {
        // If they exist in the queue, DO NOT unset them. 
        // Update their status and role to match their current state in the UI.
        $this->groupRows[$index]['status'] = $status;
        $this->groupRows[$index]['role']   = $role;
        $this->groupRows[$index]['name']   = $name;
    } else {
        // If they don't exist in the queue, add them.
        if (count($this->groupRows) === 1 && empty($this->groupRows[0]['user_id'])) {
            $this->groupRows[0] = [
                'user_id' => $userId, 
                'name'    => $name, 
                'role'    => $role, 
                'status'  => $status
            ];
        } else {
            $this->groupRows[] = [
                'user_id' => $userId, 
                'name'    => $name, 
                'role'    => $role, 
                'status'  => $status
            ];
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

public function updateGroupRole($userId, $name, $role)
{
    // Find the specific user in the groupRows array
    $index = collect($this->groupRows)->search(function ($row) use ($userId) {
        return isset($row['user_id']) && $row['user_id'] == $userId;
    });

    if ($index !== false) {
        // If they are already in the queue, just update their role
        $this->groupRows[$index]['role'] = $role;
    } else {
        // If they aren't in the queue, add them with their name so the role change is saved
        if (count($this->groupRows) === 1 && empty($this->groupRows[0]['user_id'])) {
            $this->groupRows[0] = ['user_id' => $userId, 'name' => $name, 'role' => $role, 'status' => 'member'];
        } else {
            $this->groupRows[] = ['user_id' => $userId, 'name' => $name, 'role' => $role, 'status' => 'member'];
        }
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