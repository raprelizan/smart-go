<?php

namespace App\Models;

class Product extends TenantModel
{
    protected $fillable = [
        'merchant_id',
        'store_id',
        'name',
        'slug',
        'price',
        'description',
        'status',
    ];
}
