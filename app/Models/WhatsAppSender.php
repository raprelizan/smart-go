<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppSender extends Model
{
    protected $fillable = [
        'name',
        'phone_number_id',
        'access_token_encrypted',
        'weight',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];
}
