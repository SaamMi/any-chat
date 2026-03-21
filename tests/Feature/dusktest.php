<?php

it('renders the widget on the test page', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/anychat-test') // The route from your ServiceProvider
            ->waitForText('Chat with us')
            ->click('@chat-trigger')
            ->assertVisible('#chatbox');
    });
});
