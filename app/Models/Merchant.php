<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Merchant extends Model
{
    protected $fillable = [
        'full_name',
        'business_name',
        'email',
        'phone',
        'whatsapp_receive_number',
        'status',
        'notes',
        'slug',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
