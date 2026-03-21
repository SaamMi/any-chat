<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class WidgetTest extends DuskTestCase
{
    /** @test */
    public function test_renders_the_widget_on_the_test_page()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/anychat-test')
                ->waitForText('Chat with us', 10) // Adding a 10s timeout just in case
                ->assertVisible('[dusk="chat-trigger"]');
        });
    }
}
