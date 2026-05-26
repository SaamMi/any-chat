<?php

namespace SaamMi\AnyChat\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Participant extends Model
{
   
   
 protected $fillable = [
        'conversation_id',
        'participantable_id',
        'participantable_type',
        'role',
        'exited_at',
        'conversation_deleted_at',
        'conversation_cleared_at',
        'conversation_read_at',
        'last_active_at',
    ];


     public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

     public function participantable(): MorphTo
    {
        return $this->morphTo();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'participant_id');
    }

  
    public function latestMessage()
    {
        return $this->hasOne(Message::class, 'participant_id')->latestOfMany();
    }

}
