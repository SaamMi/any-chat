<?php

namespace SaamMi\AnyChat\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;



class Conversation extends Model
{
   
    
    protected $fillable = [
        'type',
        'disappearing_started_at',
        'disappearing_duration',
    ];

   
     public function participants(): HasMany
    {
        return $this->hasMany(Participant::class, 'conversation_id', 'id');
    }

    public function messages(): hasMany
    {
        return $this->hasMany(Message::class);
    }

    public function lastMessage(): hasOne
    {
        return $this->hasOne(Message::class, 'conversation_id')->latestOfMany();
    }

}
