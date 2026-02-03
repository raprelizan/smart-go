<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Product;
use App\Models\Store;
use App\Services\ThemeRenderer;

class StorefrontController extends Controller
{
    public function store(string $storeSlug, ThemeRenderer $renderer)
    {
        $store = Store::where('slug', $storeSlug)->firstOrFail();
        $page = Page::where('slug', $storeSlug)->where('status', 'PUBLISHED')->first();

        return view('storefront', [
            'content' => $page ? $renderer->render($page->content, ['store' => $store]) : '',
        ]);
    }

    public function product(string $storeSlug, string $productSlug, ThemeRenderer $renderer)
    {
        $store = Store::where('slug', $storeSlug)->firstOrFail();
        $product = Product::where('slug', $productSlug)->firstOrFail();
        $page = Page::where('product_id', $product->id)->where('status', 'PUBLISHED')->first();

        return view('storefront', [
            'content' => $page ? $renderer->render($page->content, ['store' => $store, 'product' => $product]) : '',
        ]);
    }
}
