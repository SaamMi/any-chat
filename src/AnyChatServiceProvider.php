<?php

namespace SaamMi\AnyChat;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use SaamMi\AnyChat\Livewire\Publicchat;
use SaamMi\AnyChat\Livewire\PublicResponse;

class AnyChatServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'anychat');

        // Register the component
        Livewire::component('anychat-widget', Publicchat::class);
        Blade::component('anychat', Publicchat::class);

        Livewire::component('anychat-dashboard', PublicResponse::class);

        // ONLY register the route for testing or local development
        if ($this->app->environment('local', 'testing')) {
            $this->registerTestRoutes();
        }
    }

    protected function registerTestRoutes()
    {
        Route::middleware('web')->group(function () {
            Route::get('/anychat-test', function () {
                // This returns the 'parent' view you created for testing
                return view('anychat.livewire::test-chat');
            })->name('anychat.test');
            // NEW: Simple smoke test route
            Route::get('/smoke-test', function () {
                return view('anychat::smoke-test');
            });
        });
    }
}
