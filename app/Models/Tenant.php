<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'limite_consultas_mensual',
        'activo',
    ];

    /**
     * Get the users that belong to the tenant.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
