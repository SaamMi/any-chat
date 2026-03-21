<?php

namespace SaamMi\AnyChat\Models;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\BroadcastsEvents;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    // use BroadcastsEvents;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'message',
        'cookievalue',
        'auth',
        'replied',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(User::class);

    }

    /* public function broadcastOn($event): PrivateChannel
     {
         return new PrivateChannel('chat-room');
     } */

    /*  public function broadcastWith(): array
      {

          return [
          'message' => $this,
          'user' => $this->user->only('name'),
      ];
      }  */

}
