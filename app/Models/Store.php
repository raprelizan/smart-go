<?php

namespace App\Models;

class Store extends TenantModel
{
    protected $fillable = [
        'merchant_id',
        'name',
        'slug',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];
}
