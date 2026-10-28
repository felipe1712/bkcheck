<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PldExpedienteVersion extends Model
{
    use HasFactory;

    protected $table = 'pld_expediente_versiones';

    protected $fillable = [
        'folio',
        'tenant_id',
        'subject_id',
        'expediente_pld_id',
        'version',
        'tipo_ejecucion',
        'folio_corregido',
        'fecha_generacion',
        'hash_version',
        'hash_previo',
        'payload_canonico',
        'expediente_formateado_texto',
        'cambios_resumen',
        'schema_version',
    ];

    protected $casts = [
        'version'          => 'integer',
        'fecha_generacion' => 'datetime',
        'cambios_resumen'  => 'array',
        'schema_version'   => 'integer',
    ];

    public function resultadosFuentes()
    {
        return $this->hasMany(PldFuenteResultado::class, 'folio', 'folio');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class)->withoutGlobalScopes();
    }

    public function getDecodedPayloadAttribute(): array
    {
        return json_decode($this->payload_canonico, true) ?: [];
    }
}
