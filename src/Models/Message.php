<?php

namespace SaamMi\AnyChat\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
     protected $fillable = [
        'body',
        'participant_id',
        'reply_id',
        'conversation_id',
        'type',
        'kept_at',
    ];

      public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

     public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class, 'participant_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

}
