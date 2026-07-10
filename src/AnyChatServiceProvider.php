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
        Route::middleware('web')->group(function () {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
          /* Load channel routes */
        $this->loadRoutesFrom(__DIR__.'/../routes/channels.php');

    });
        
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

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/anychat'),
            ], 'anychat-views');
        }
        if ($this->app->runningInConsole()) {
        $this->publishes([
            __DIR__.'/../database/seeders' => database_path('seeders/vendor/anychat'),
        ], 'anychat-seeders');
    }

    $filePath = base_path('resources/views/layouts/app/sidebar.blade.php');

if (file_exists($filePath)) {
    $content = file_get_contents($filePath);
if (! str_contains($content,"{{ __('chat') }}")) {

    $search = '<flux:spacer />';

    $replace = <<<EOT
<a href="{{ route('chatresponse') }}"> {{ __('chat') }} </a>

            <flux:spacer />
EOT;

    $newContent = str_replace($search, $replace, $content);
    file_put_contents($filePath, $newContent);
}
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
