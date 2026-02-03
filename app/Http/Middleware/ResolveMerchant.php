<?php

namespace App\Http\Middleware;

use App\Models\Merchant;
use Closure;
use Illuminate\Http\Request;

class ResolveMerchant
{
    public function handle(Request $request, Closure $next)
    {
        $merchantId = $request->user()?->merchant_id;

        if ($request->route('storeSlug')) {
            $merchantId = Merchant::query()
                ->where('slug', $request->route('storeSlug'))
                ->value('id');
        }

        if ($merchantId) {
            $request->attributes->set('merchant_id', $merchantId);
        }

        return $next($request);
    }
}
