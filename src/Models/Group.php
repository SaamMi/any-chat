<?php

namespace SaamMi\AnyChat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use SaamMi\AnyChat\Models\TeamGroups;




class Group extends Model
{
    

protected $fillable = [

        
        'name',
        'conversation_id',
        'description',
        'avatar_url',
        
    ];


      public function groupMembers()
    {
        return $this->belongsToMany(User::class, 'team_groups', 'group_id', 'user_id')
            ->using(TeamGroups::class)
            ->withPivot(['role'])
            ->withTimestamps();
    }


    
    
}
