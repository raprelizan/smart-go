<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShippingRule;
use Illuminate\Http\Request;

class ShippingRuleController extends Controller
{
    public function index()
    {
        return ShippingRule::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'nullable|integer',
            'wilaya_id' => 'required|integer',
            'price' => 'required|numeric',
            'is_enabled' => 'required|boolean',
        ]);

        $data['merchant_id'] = $request->user()->merchant_id;

        return ShippingRule::create($data);
    }

    public function show(ShippingRule $shippingRule)
    {
        return $shippingRule;
    }

    public function update(Request $request, ShippingRule $shippingRule)
    {
        $data = $request->validate([
            'price' => 'sometimes|numeric',
            'is_enabled' => 'sometimes|boolean',
        ]);

        $shippingRule->update($data);

        return $shippingRule;
    }

    public function destroy(ShippingRule $shippingRule)
    {
        $shippingRule->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
