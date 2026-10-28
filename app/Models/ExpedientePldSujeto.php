<?php

namespace App\Models;

use App\Services\Pld\PldCryptoService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpedientePldSujeto extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'expedientes_pld_sujetos';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'tenant_id',
        'subject_id',
        'rfc_cifrado',
        'rfc_bindex',
        'tipo_persona',
        'score_global',
        'scores_categoria',
        'nivel_riesgo_persona',
        'recomendacion',
        'edd_recomendada',
        'edd_causa',
        'factores_riesgo_json',
        'hallazgos_json',
        'fuentes_pendientes_json',
        'conservar_hasta',
        'finalidad',
        'folio_vigente',
        'version_vigente',
        'proxima_reevaluacion',
    ];

    protected $casts = [
        'score_global'            => 'integer',
        'scores_categoria'        => 'array',
        'edd_recomendada'         => 'boolean',
        'factores_riesgo_json'    => 'array',
        'hallazgos_json'          => 'array',
        'fuentes_pendientes_json' => 'array',
        'conservar_hasta'         => 'datetime',
        'version_vigente'         => 'integer',
        'proxima_reevaluacion'    => 'datetime',
    ];

    /**
     * Asigna automáticamente rfc_cifrado (AES-256-GCM) y rfc_bindex (HMAC-SHA256) a partir de un RFC en claro.
     */
    public function setPlainRfc(?string $rfc): void
    {
        $crypto = app(PldCryptoService::class);
        $normalized = $crypto->normalizeRfc($rfc);
        $this->rfc_cifrado = $crypto->encrypt($normalized);
        $this->rfc_bindex  = $crypto->blindIndexRfc($normalized);
    }

    /**
     * Descifra el RFC usando AES-256-GCM verificando su tag de 128 bits.
     */
    public function getDecryptedRfcAttribute(): ?string
    {
        if (empty($this->rfc_cifrado)) {
            return null;
        }
        return app(PldCryptoService::class)->decrypt($this->rfc_cifrado);
    }

    /**
     * Scope de búsqueda ciega exacta por RFC vía rfc_bindex sin descifrar la tabla (M2).
     */
    public function scopeWhereRfcBlindIndex(Builder $query, string $rfc): Builder
    {
        $bindex = app(PldCryptoService::class)->blindIndexRfc($rfc);
        return $query->where('rfc_bindex', $bindex);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class)->withoutGlobalScopes();
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function versiones()
    {
        return $this->hasMany(PldExpedienteVersion::class, 'expediente_pld_id', 'id')->orderBy('version');
    }
}
