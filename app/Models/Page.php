<?php

namespace App\Models;

class Page extends TenantModel
{
    protected $fillable = [
        'merchant_id',
        'product_id',
        'name',
        'slug',
        'version',
        'status',
        'content',
    ];

    protected $casts = [
        'content' => 'array',
    ];
}
