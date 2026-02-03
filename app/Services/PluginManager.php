<?php

namespace App\Services;

use App\Models\Plugin;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class PluginManager
{
    public function __construct(private Filesystem $files)
    {
    }

    public function discover(): Collection
    {
        $plugins = [];
        foreach ($this->files->directories(base_path('plugins')) as $path) {
            $manifestPath = $path . '/plugin.json';
            if (!$this->files->exists($manifestPath)) {
                continue;
            }
            $manifest = json_decode($this->files->get($manifestPath), true, 512, JSON_THROW_ON_ERROR);
            $plugins[] = array_merge($manifest, ['path' => $path]);
        }

        return collect($plugins);
    }

    public function install(string $name): Plugin
    {
        $manifest = $this->findManifest($name);
        return Plugin::updateOrCreate(
            ['name' => $manifest['name']],
            [
                'version' => Arr::get($manifest, 'version'),
                'description' => Arr::get($manifest, 'description'),
                'is_installed' => true,
                'is_enabled' => false,
            ]
        );
    }

    public function enable(string $name): void
    {
        Plugin::where('name', $name)->update(['is_enabled' => true]);
    }

    public function disable(string $name): void
    {
        Plugin::where('name', $name)->update(['is_enabled' => false]);
    }

    public function findManifest(string $name): array
    {
        $plugin = $this->discover()->firstWhere('name', $name);
        if (!$plugin) {
            throw new \RuntimeException("Plugin {$name} not found.");
        }
        return $plugin;
    }
}
