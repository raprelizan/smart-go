<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\PluginManager;
use Illuminate\Http\Request;

class PluginController extends Controller
{
    public function index(PluginManager $manager)
    {
        return $manager->discover();
    }

    public function store(Request $request, PluginManager $manager)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);

        return $manager->install($data['name']);
    }

    public function update(Request $request, string $plugin, PluginManager $manager)
    {
        $data = $request->validate([
            'action' => 'required|in:enable,disable',
        ]);

        if ($data['action'] === 'enable') {
            $manager->enable($plugin);
        } else {
            $manager->disable($plugin);
        }

        return response()->json(['message' => 'Updated']);
    }

    public function destroy(string $plugin, PluginManager $manager)
    {
        $manager->disable($plugin);

        return response()->json(['message' => 'Disabled']);
    }
}
