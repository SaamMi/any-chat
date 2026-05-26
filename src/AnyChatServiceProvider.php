<?php

namespace SaamMi\AnyChat;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use SaamMi\AnyChat\Livewire\Publicchat;
use SaamMi\AnyChat\Livewire\PublicResponse;
use SaamMi\AnyChat\Contracts\AiCopilot;
use SaamMi\AnyChat\Services\NullAiCopilot;



class AnyChatServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'anychat');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Register the component
        Livewire::component('anychat-widget', Publicchat::class);
        Blade::component('anychat', Publicchat::class);

        Livewire::component('anychat-dashboard', PublicResponse::class);

        // ONLY register the route for testing or local development
        if ($this->app->environment('local', 'testing')) {
            $this->registerTestRoutes();
        }
    }

    public function register()
{


    $this->app->bindIf(AiCopilot::class, NullAiCopilot::class);
}

    protected function registerTestRoutes()
    {
        Route::middleware('web')->group(function () {
            Route::get('/anychat-test', function () {
                // This returns the 'parent' view you created for testing
                return view('test-chat');
            })->name('anychat.test');

        });
    }
}
