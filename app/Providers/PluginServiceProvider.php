<?php

namespace App\Providers;

use App\Services\PluginManager;
use Illuminate\Support\ServiceProvider;

class PluginServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PluginManager::class, function ($app) {
            return new PluginManager($app['files']);
        });
    }

    public function boot(PluginManager $manager): void
    {
        $manager->discover()->each(function (array $plugin) {
            $provider = $plugin['provider'] ?? null;
            if ($provider && class_exists($provider)) {
                $this->app->register($provider);
            }
        });
    }
}
