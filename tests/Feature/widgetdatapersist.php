<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
// use SaamMi\AnyChat\Tests\TestCase;
use Livewire\Livewire;
use SaamMi\AnyChat\Livewire\Publicchat;

uses(RefreshDatabase::class);

it('can create a post via livewire', function () {
    // 1. Arrange: Ensure the DB is empty
    $this->assertDatabaseCount('messages', 0);

    // 2. Act: Interact with the component
    Livewire::test(Publicchat::class)
        ->set('message', 'My Awesome Package Post')
        ->call('sendMessage')
        ->assertHasNoErrors();

    // 3. Assert: Check the database persistence
    $this->assertDatabaseHas('messages', [
        'message' => 'My Awesome Package Post',
    ]);
});
