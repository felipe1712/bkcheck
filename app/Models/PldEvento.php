<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PldEvento extends Model
{
    use HasFactory;

    protected $table = 'pld_eventos';
    protected $primaryKey = 'evento_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'evento_id',
        'tenant_id',
        'suscripcion_id',
        'subject_id',
        'tipo',
        'folio_origen',
        'payload_webhook',
        'fecha',
        'entregado_en',
        'intentos',
        'ultimo_error',
    ];

    protected $casts = [
        'payload_webhook' => 'array',
        'fecha'           => 'datetime',
        'entregado_en'    => 'datetime',
        'intentos'        => 'integer',
    ];

    public function suscripcion()
    {
        return $this->belongsTo(PldSuscripcion::class, 'suscripcion_id', 'suscripcion_id');
    }
}
