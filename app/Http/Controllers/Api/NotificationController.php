<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationFormatter;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function test(Request $request, NotificationFormatter $formatter)
    {
        $order = $request->user()->merchant?->orders()->latest()->first();
        if (!$order) {
            return response()->json(['message' => 'No orders found'], 404);
        }

        return response()->json([
            'message' => $formatter->format($order, $request->input('locale', 'en')),
        ]);
    }
}
