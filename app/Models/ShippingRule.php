<?php

namespace App\Models;

class ShippingRule extends TenantModel
{
    protected $fillable = [
        'merchant_id',
        'product_id',
        'wilaya_id',
        'price',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];
}
