<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function index()
    {
        return Page::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'version' => 'required|integer',
            'status' => 'required|string',
            'content' => 'required|array',
        ]);

        $data['merchant_id'] = $request->user()->merchant_id;

        return Page::create($data);
    }

    public function show(Page $page)
    {
        return $page;
    }

    public function update(Request $request, Page $page)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255',
            'version' => 'sometimes|integer',
            'status' => 'sometimes|string',
            'content' => 'sometimes|array',
        ]);

        $page->update($data);

        return $page;
    }

    public function destroy(Page $page)
    {
        $page->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
