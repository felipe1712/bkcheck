<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Subject extends Model
{
    use HasFactory, HasTenant;

    protected $fillable = [
        'project_id',
        'tipo',
        'name_or_company',
        'rfc',
        'curp',
        'address',
        'consent_granted',
        'consent_date',
        'consent_legal_basis',
        'consent_document_path',
        'ine_front_path',
        'ine_back_path',
        'tenant_id',
        // Enrollment fields
        'enrollment_token',
        'enrollment_expires_at',
        'enrollment_completed_at',
        'enrollment_ip',
        'enrollment_tc_accepted_at',
        'selfie_path',
        'username',
        'proof_of_address_path',
        'nss',
        'credit_consent_granted',
        'credit_consent_at',
        'tier_level',
    ];

    protected $casts = [
        'tier_level'                => 'integer',
        'consent_granted'           => 'boolean',
        'consent_date'              => 'datetime',
        'enrollment_expires_at'     => 'datetime',
        'enrollment_completed_at'   => 'datetime',
        'enrollment_tc_accepted_at' => 'datetime',
        'credit_consent_granted'    => 'boolean',
        'credit_consent_at'         => 'datetime',
        'rfc'                       => 'encrypted',
        'curp'                      => 'encrypted',
        'address'                   => 'encrypted',
        'nss'                       => 'encrypted',  // NSS es dato sensible — encriptado en BD
    ];

    /**
     * Get the project that the subject belongs to.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Generate a unique enrollment token with a 24-hour expiration.
     */
    public function generateEnrollmentToken(): void
    {
        $this->enrollment_token         = Str::uuid()->toString();
        $this->enrollment_expires_at    = now()->addHours(24);
        $this->enrollment_completed_at  = null;
        $this->enrollment_tc_accepted_at = null;
        $this->save();
    }

    /**
     * Determine if the enrollment link is currently active (not expired, not completed).
     */
    public function isEnrollmentActive(): bool
    {
        if (empty($this->enrollment_token)) {
            return false;
        }
        if ($this->enrollment_completed_at) {
            return false;
        }
        if ($this->enrollment_expires_at && $this->enrollment_expires_at->isPast()) {
            return false;
        }
        return true;
    }

    /**
     * Get the enrollment status label.
     */
    public function enrollmentStatus(): string
    {
        if (empty($this->enrollment_token)) {
            return 'sin_token';
        }
        if ($this->enrollment_completed_at) {
            return 'completado';
        }
        if ($this->enrollment_expires_at && $this->enrollment_expires_at->isPast()) {
            return 'expirado';
        }
        if ($this->enrollment_tc_accepted_at) {
            return 'en_proceso';
        }
        return 'pendiente';
    }
}

