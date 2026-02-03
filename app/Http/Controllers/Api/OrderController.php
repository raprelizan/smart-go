<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingRule;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        return Order::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|integer',
            'store_id' => 'required|integer',
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:32',
            'full_address' => 'required|string|max:255',
            'wilaya_id' => 'required|integer',
            'notes' => 'nullable|string',
        ]);

        $product = Product::findOrFail($data['product_id']);
        $shipping = ShippingRule::query()
            ->where('product_id', $product->id)
            ->where('wilaya_id', $data['wilaya_id'])
            ->where('is_enabled', true)
            ->first();

        $shippingPrice = $shipping?->price ?? 0;
        $total = $product->price + $shippingPrice;

        $order = Order::create([
            'merchant_id' => $request->user()->merchant_id,
            'product_id' => $product->id,
            'store_id' => $data['store_id'],
            'full_name' => $data['full_name'],
            'phone' => $data['phone'],
            'full_address' => $data['full_address'],
            'wilaya_id' => $data['wilaya_id'],
            'notes' => $data['notes'] ?? null,
            'price' => $product->price,
            'shipping_price' => $shippingPrice,
            'total' => $total,
            'status' => 'NEW',
        ]);

        event(new \App\Events\OrderCreated($order));

        return $order;
    }

    public function show(Order $order)
    {
        return $order;
    }

    public function update(Request $request, Order $order)
    {
        $data = $request->validate([
            'status' => 'required|string',
        ]);

        $order->update($data);

        event(new \App\Events\OrderStatusChanged($order));

        return $order;
    }

    public function destroy(Order $order)
    {
        $order->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
