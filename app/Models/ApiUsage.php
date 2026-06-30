<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiUsage extends Model
{
    use HasFactory, HasTenant;

    protected $table = 'api_usage';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'servicio',
        'periodo',
        'conteo',
        'costo_estimado',
        'ingreso_estimado',
    ];

    /**
     * Get the user that generated this usage.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
