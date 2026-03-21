<?php

namespace SaamMi\AnyChat\Tests;

use Livewire\LivewireServiceProvider;
use SaamMi\AnyChat\AnyChatServiceProvider;

// use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // additional setup
    }

    protected function getPackageProviders($app)
    {
        return [
            LivewireServiceProvider::class,
            AnyChatServiceProvider::class,

        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.key', 'base64:HBy6v9G99YF2Iq8H8F7B5p9K9f2e3r4t5y6u7i8o9p0=');

    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/../workbench/database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        //  $this->loadMigrationsFrom(workbench_path('database/migrations'));

    }
}
