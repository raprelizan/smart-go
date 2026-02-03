<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasRoles;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'merchant_id',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
}
