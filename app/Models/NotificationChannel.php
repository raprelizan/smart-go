<?php

namespace App\Models;

class NotificationChannel extends TenantModel
{
    protected $fillable = [
        'merchant_id',
        'channel',
        'settings',
        'is_enabled',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_enabled' => 'boolean',
    ];
}
