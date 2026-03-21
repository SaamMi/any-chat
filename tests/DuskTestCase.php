<?php

namespace Tests;

use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\Dusk\TestCase as OrchestraDusk;
use SaamMi\AnyChat\AnyChatServiceProvider;

abstract class DuskTestCase extends OrchestraDusk
{
    protected function getPackageProviders($app)
    {
        return [
            LivewireServiceProvider::class,
            AnyChatServiceProvider::class,
        ];
    }

    /**
     * This defines routes specifically for the browser instance
     * to visit during the test.
     */
    protected function defineRoutes($router)
    {
        $router->get('/anychat-test', function () {
            // Ensure you have a 'test-chat.blade.php' in your package's views
            return view('anychat::test-chat');
        })->middleware('web');
    }

    protected function getEnvironmentSetUp($app)
    {

        $app['config']->set('app.key', 'base64:OTY4Y214YTMycW93NHZueXp3cmZ0Z3loYm5tancxcXo=');
    }
}
