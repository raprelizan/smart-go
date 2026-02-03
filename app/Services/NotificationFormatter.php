<?php

namespace App\Services;

use App\Models\Order;

class NotificationFormatter
{
    public function format(Order $order, string $locale = 'en'): string
    {
        $lines = [
            $locale === 'ar' ? 'طلب جديد' : 'New Order',
            "Store: {$order->store_id}",
            "Product: {$order->product_id}",
            "Price: {$order->price}",
            "Shipping: {$order->shipping_price}",
            "Total: {$order->total}",
            "Customer: {$order->full_name}",
            "Phone: {$order->phone}",
            "Wilaya: {$order->wilaya_id}",
            "Address: {$order->full_address}",
            "Time: {$order->created_at}",
        ];

        return implode("\n", $lines);
    }
}
