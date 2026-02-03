<?php

namespace Plugins\WhatsAppNotifierPlugin;

use App\Events\OrderCreated;
use App\Models\NotificationDelivery;
use App\Services\NotificationFormatter;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class WhatsAppNotifierServiceProvider extends ServiceProvider
{
    public function boot(NotificationFormatter $formatter): void
    {
        Event::listen(OrderCreated::class, function (OrderCreated $event) use ($formatter) {
            $message = $formatter->format($event->order, 'en');

            NotificationDelivery::create([
                'merchant_id' => $event->order->merchant_id,
                'order_id' => $event->order->id,
                'channel' => 'whatsapp',
                'payload' => ['message' => $message],
                'response' => ['status' => 'queued'],
                'status' => 'queued',
                'error_message' => null,
            ]);
        });
    }
}
