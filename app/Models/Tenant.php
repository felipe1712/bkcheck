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
        'enrollment_terms',
        'enrollment_terms_updated_at',
    ];

    protected $casts = [
        'activo'                       => 'boolean',
        'enrollment_terms_updated_at'  => 'datetime',
    ];

    /**
     * Get the users that belong to the tenant.
     */
    public function users()
    {
        return $this->hasMany(User::class);
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

