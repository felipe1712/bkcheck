<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PldScoringPolicy extends Model
{
    use HasFactory;

    protected $table = 'pld_scoring_policies';

    protected $fillable = [
        'tenant_id',
        'categoria',
        'peso_porcentual',
        'triggers_config',
        'intervalo_reevaluacion_bajo_medio_meses',
        'intervalo_reevaluacion_alto_meses',
    ];

    protected $casts = [
        'peso_porcentual'                        => 'integer',
        'triggers_config'                        => 'array',
        'intervalo_reevaluacion_bajo_medio_meses'=> 'integer',
        'intervalo_reevaluacion_alto_meses'      => 'integer',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Siembra las 4 categorías de política de puntuación por defecto para un Tenant si no existen.
     */
    public static function seedDefaultsForTenant(int $tenantId): void
    {
        $defaults = config('pld.policy_defaults', []);
        $intervalos = config('pld.intervalos_default', ['bajo_medio_meses' => 6, 'alto_meses' => 3]);

        foreach ($defaults as $categoria => $cfg) {
            static::firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'categoria' => $categoria,
                ],
                [
                    'peso_porcentual'                         => $cfg['peso_porcentual'],
                    'triggers_config'                         => $cfg['triggers_config'],
                    'intervalo_reevaluacion_bajo_medio_meses' => $intervalos['bajo_medio_meses'] ?? 6,
                    'intervalo_reevaluacion_alto_meses'       => $intervalos['alto_meses'] ?? 3,
                ]
            );
        }
    }
}
