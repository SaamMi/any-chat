<?php

namespace SaamMi\AnyChat\Livewire;

use Livewire\Component;
use SaamMi\AnyChat\Models\Message;

class CreatePost extends Component
{
    public $message;

    public function save()
    {
        Message::create([
            'message' => $this->message, // Use the passed parameter, not $this->message
            'cookievalue' => 'cookievalue',
            'auth' => false,
            'replied' => true,
        ]);
        //  Post::create(['title' => $this->title]);
    }

    public function render()
    {
        return view('anychat::livewire.create-post');
    }
}
