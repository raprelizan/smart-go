<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plugin extends Model
{
    protected $fillable = [
        'name',
        'version',
        'description',
        'is_enabled',
        'is_installed',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_installed' => 'boolean',
    ];
}
