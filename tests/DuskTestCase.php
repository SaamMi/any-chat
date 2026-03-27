<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver; 
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

    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions)->addArguments([
            '--disable-gpu',
            '--headless=new', // Use the new headless engine
            '--no-sandbox',
            '--window-size=1920,1080',
        ]);

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }

    protected function getEnvironmentSetUp($app)
    {

        $app['config']->set('app.key', 'base64:OTY4Y214YTMycW93NHZueXp3cmZ0Z3loYm5tancxcXo=');
    }
}
