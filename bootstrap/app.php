<?php

use App\Providers\PluginServiceProvider;

$app = new Illuminate\Foundation\Application(dirname(__DIR__));

$app->register(PluginServiceProvider::class);

return $app;
