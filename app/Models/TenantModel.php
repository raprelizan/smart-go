<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

abstract class TenantModel extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $user = Auth::user();
            if ($user && !$user->hasRole('super-admin')) {
                $builder->where($builder->getModel()->getTable() . '.merchant_id', $user->merchant_id);
            }
        });
    }
}
