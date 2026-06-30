<?php

namespace App\Traits;

use App\Scopes\TenantScope;
use Illuminate\Support\Facades\Auth;

trait HasTenant
{
    /**
     * Boot the trait to add the global scope and creating listener.
     */
    public static function bootHasTenant()
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (Auth::check() && Auth::user()->tenant_id !== null) {
                $model->tenant_id = Auth::user()->tenant_id;
            }
        });
    }

    /**
     * Get the tenant that owns the model.
     */
    public function tenant()
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }
}
