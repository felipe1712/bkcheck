<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PldSuscripcion extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pld_suscripciones';
    protected $primaryKey = 'suscripcion_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'suscripcion_id',
        'tenant_id',
        'subject_id',
        'finalidad',
        'consentimiento_ref',
        'consentimiento_vigente',
        'intervalo_meses',
        'estado',
        'motivo_baja',
        'proxima_reevaluacion',
        'ultima_reevaluacion_identidad',
        'fallos_consecutivos_por_fuente',
    ];

    protected $casts = [
        'consentimiento_vigente'          => 'boolean',
        'intervalo_meses'                 => 'integer',
        'proxima_reevaluacion'            => 'datetime',
        'ultima_reevaluacion_identidad'   => 'datetime',
        'fallos_consecutivos_por_fuente'  => 'array',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class)->withoutGlobalScopes();
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function eventos()
    {
        return $this->hasMany(PldEvento::class, 'suscripcion_id', 'suscripcion_id');
    }
}
