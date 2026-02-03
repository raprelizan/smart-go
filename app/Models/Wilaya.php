<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wilaya extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
    ];
}
