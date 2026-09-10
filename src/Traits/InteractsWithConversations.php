<?php

namespace SaamMi\AnyChat\Traits;

use SaamMi\AnyChat\Models\Message;
use SaamMi\AnyChat\Models\Participant;
use SaamMi\AnyChat\Models\Conversation;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;

trait InteractsWithConversations
{
    /**
     * Link the model to the participants table
     */
    public function participations(): MorphMany
    {
        return $this->morphMany(Participant::class, 'participantable');
    }

/**
     * Determines if the current request should be persisted to the DB.
     */
    public function resolvePersistenceMode()
    {
       
             return $this->persistenceMode;
        

       
    }
    /**
     * For the Chat Widget: Finds the unique 1-to-1 thread between two participants.
     * Use this in Publicchat.php.
     */
    public function getDirectConversationWith($targetId, $targetType)
    {
        
$myId = $this->getKey(); // Returns hexstring for Guest, int for User
    $myType = get_class($this);

    $conversation = Conversation::where('type', 'direct')
        ->whereHas('participants', fn($q) => $q->where([
            'participantable_id' => $myId,
            'participantable_type' => $myType
        ]))
          ->whereHas('participants', fn($q) => $q->where([
                'participantable_id' => $targetId,
                'participantable_type' => $targetType
            ]))
            ->first();

            //dd($conversation);


        if (!$conversation) {
            $conversation = Conversation::create(['type' => 'direct']);
            
            // Link both as participants (Me and the Target)
            $conversation->participants()->createMany([
                ['participantable_id' => $myId, 'participantable_type' => $myType, 'role' => 'owner'],
                ['participantable_id' => $targetId, 'participantable_type' => $targetType, 'role' => 'participant'],
            ]);
        }

        return $conversation;
    
}

    /**
     * For the Dashboard: Creates a unique group thread.
     */
    public function getGroupConversationWith($id, $type, $conversationId)
    {

     $admin = Auth::user();
       // $groupMembers = $admin->currentTeam->groupMemberships()->get();


$members = $admin->currentTeam->members()->get()->map(fn ($member) => [
            'id' => $member->id,
            'name' => $member->name,
            'email' => $member->email,
            'avatar' => $member->avatar ?? null,
            'initials' => $member->initials(),
            'role' => $member->pivot->role->value,
            'role_label' => $member->pivot->role->label(),
        ])->toArray();

        //dd($members);

       


     $conversation = Conversation::where('type', 'group')
        ->whereHas('participants', fn($q) => $q->where([
            'conversation_id' => $conversationId,
            'participantable_type' => get_class($admin)
        ]))
         
            ->first();

           // dd($conversation);

           if (!$conversation) {
            $conversation = Conversation::create(['type' => 'group']);
            
          // this needs to be checked,we have to pass the user_id to the  'participantable_id' field not the groupId
           $conversation->participants()->create([
            'participantable_id' => $id, 
            'participantable_type' => get_class($admin), 
            'role' => 'owner'
        ]);

             // Add invited members
        foreach ($members as $data) {
            $conversation->participants()->create([
                'participantable_id' => $data['id'],
                'participantable_type' => get_class($admin),
                'role' => 'member' //to be changed from group_members table
            ]);
        }
        }
  // dd($conversation);
     return $conversation;
    
    
    
    
    
    
    
    
    
    
    
    
    
    
   /* $conversation = Conversation::create([
            'type' => 'group',
            'name' => $name, // Ensure your migration has a 'name' column
        ]);

        // Add the creator
        $conversation->participants()->create([
            'participantable_id' => $this->participantable_id,
            'participantable_type' => get_class($this),
            'role' => 'admin'
        ]);

        // Add invited members
        foreach ($participantData as $data) {
            $conversation->participants()->create([
                'participantable_id' => $data['id'],
                'participantable_type' => $data['type'],
                'role' => 'member'
            ]);
        }

        return $conversation; */
    }


}