<?php

namespace App\Models;

class Order extends TenantModel
{
    protected $fillable = [
        'merchant_id',
        'product_id',
        'store_id',
        'full_name',
        'phone',
        'full_address',
        'wilaya_id',
        'notes',
        'price',
        'shipping_price',
        'total',
        'status',
    ];
}
