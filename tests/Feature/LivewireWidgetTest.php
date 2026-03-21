<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Crypt; // We reuse this to ensure the same ServiceProviders load
use Livewire\Livewire;
use SaamMi\AnyChat\Livewire\Publicchat;
use Tests\DuskTestCase;

class LivewireWidgetTest extends DuskTestCase
{
    /** @test */
    public function test_can_perform_initial_handshake()
    {
        Livewire::test(Publicchat::class)
            ->call('sendMessage', 'Hello Handshake')
            ->assertDispatched('token-handshake')
            ->assertSet('activeChatId', fn ($value) => !empty($value));
    }

    /** @test */
    public function test_can_decrypt_existing_token_from_header()
    {
        // 1. Create a fake token
        $chatId = 'test-chat-123';
        $token = Crypt::encryptString(json_encode(['chatId' => $chatId]));

        // 2. Set the header and test the component
        Livewire::withHeaders(['X-AnyChat-Token' => $token])
            ->test(Publicchat::class)
            ->call('sendMessage', 'Checking decryption')
            // If this works, activeChatId should match our fake one
            ->assertSet('activeChatId', $chatId);
    }
}
