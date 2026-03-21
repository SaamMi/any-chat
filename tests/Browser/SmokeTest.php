<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class SmokeTest extends DuskTestCase
{
    /** @test */
    public function test_can_see_the_smoke_test_page()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/smoke-test')
                ->assertSee('Dusk is Working!')
                ->click('[dusk="test-button"]')
                ->assertSee('Button Clicked!');
        });
    }
}
