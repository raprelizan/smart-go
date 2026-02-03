<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function index(Request $request)
    {
        return Store::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'settings' => 'array',
        ]);

        $data['merchant_id'] = $request->user()->merchant_id;

        return Store::create($data);
    }

    public function show(Store $store)
    {
        return $store;
    }

    public function update(Request $request, Store $store)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255',
            'settings' => 'sometimes|array',
        ]);

        $store->update($data);

        return $store;
    }

    public function destroy(Store $store)
    {
        $store->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
