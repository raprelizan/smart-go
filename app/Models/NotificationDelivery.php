<?php

namespace App\Models;

class NotificationDelivery extends TenantModel
{
    protected $fillable = [
        'merchant_id',
        'order_id',
        'channel',
        'payload',
        'response',
        'status',
        'error_message',
    ];

    protected $casts = [
        'payload' => 'array',
        'response' => 'array',
    ];
}
