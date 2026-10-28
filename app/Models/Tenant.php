<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'limite_consultas_mensual',
        'activo',
        'api_key',
        'enrollment_terms',
        'enrollment_terms_updated_at',
    ];

    protected $casts = [
        'activo'                       => 'boolean',
        'enrollment_terms_updated_at'  => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Tenant $tenant) {
            if (empty($tenant->api_key) && Schema::hasColumn('tenants', 'api_key')) {
                $tenant->api_key = 'avid_live_' . Str::random(40);
            }
        });

        static::created(function (Tenant $tenant) {
            if (Schema::hasTable('pld_scoring_policies')) {
                PldScoringPolicy::seedDefaultsForTenant($tenant->id);
            }
            if (Schema::hasTable('pld_fuente_catalogo')) {
                PldFuenteCatalogo::ensureDefaultsSeeded();
            }
        });
    }

    /**
     * Get the users that belong to the tenant.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function pldScoringPolicies()
    {
        return $this->hasMany(PldScoringPolicy::class);
    }

    /**
     * Ensure tenant has an API key generated.
     */
    public function ensureApiKey(): string
    {
        if (empty($this->api_key)) {
            $this->api_key = 'avid_live_' . Str::random(40);
            $this->save();
        }
        return $this->api_key;
    }

    /**
     * Check whether this tenant has configured enrollment T&C text.
     */
    public function hasEnrollmentTerms(): bool
    {
        return !empty($this->enrollment_terms);
    }

    /**
     * Return the T&C text or a generic legal fallback.
     */
    public function getEnrollmentTermsText(): string
    {
        if ($this->hasEnrollmentTerms()) {
            return $this->enrollment_terms;
        }
        return 'Al continuar, usted autoriza expresamente el uso de sus datos personales (incluyendo imágenes de su identificación oficial y fotografía personal) para fines de verificación de identidad, en cumplimiento con la Ley Federal de Protección de Datos Personales en Posesión de los Particulares (LFPDPPP). Sus datos serán tratados de forma confidencial y utilizados únicamente con la finalidad de este proceso de verificación.';
    }
}
