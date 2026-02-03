<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'merchant_id',
        'user_id',
        'action',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
