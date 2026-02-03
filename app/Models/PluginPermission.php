<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PluginPermission extends Model
{
    protected $fillable = [
        'plugin_id',
        'permission',
    ];
}
