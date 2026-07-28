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
        'liveness_id_validacion',
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
    ];

    public function getRfcAttribute($value)
    {
        if (empty($value)) return null;
        try {
            return \Illuminate\Support\Facades\Crypt::decryptString($value);
        } catch (\Throwable $e) {
            try {
                return decrypt($value);
            } catch (\Throwable $e2) {
                return $value;
            }
        }
    }

    public function setRfcAttribute($value)
    {
        $this->attributes['rfc'] = !empty($value) ? \Illuminate\Support\Facades\Crypt::encryptString($value) : null;
    }

    public function getCurpAttribute($value)
    {
        if (empty($value)) return null;
        try {
            return \Illuminate\Support\Facades\Crypt::decryptString($value);
        } catch (\Throwable $e) {
            try {
                return decrypt($value);
            } catch (\Throwable $e2) {
                return $value;
            }
        }
    }

    public function setCurpAttribute($value)
    {
        $this->attributes['curp'] = !empty($value) ? \Illuminate\Support\Facades\Crypt::encryptString($value) : null;
    }

    public function getAddressAttribute($value)
    {
        if (empty($value)) return null;
        try {
            return \Illuminate\Support\Facades\Crypt::decryptString($value);
        } catch (\Throwable $e) {
            try {
                return decrypt($value);
            } catch (\Throwable $e2) {
                return $value;
            }
        }
    }

    public function setAddressAttribute($value)
    {
        $this->attributes['address'] = !empty($value) ? \Illuminate\Support\Facades\Crypt::encryptString($value) : null;
    }

    public function getNssAttribute($value)
    {
        if (empty($value)) return null;
        try {
            return \Illuminate\Support\Facades\Crypt::decryptString($value);
        } catch (\Throwable $e) {
            try {
                return decrypt($value);
            } catch (\Throwable $e2) {
                return $value;
            }
        }
    }

    public function setNssAttribute($value)
    {
        $this->attributes['nss'] = !empty($value) ? \Illuminate\Support\Facades\Crypt::encryptString($value) : null;
    }

    public function getTierLevelAttribute($value)
    {
        return $value !== null ? (int) $value : 4;
    }

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
        if ($this->enrollment_expires_at && \Carbon\Carbon::parse($this->enrollment_expires_at)->isPast()) {
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
        if ($this->enrollment_expires_at && \Carbon\Carbon::parse($this->enrollment_expires_at)->isPast()) {
            return 'expirado';
        }
        if ($this->enrollment_tc_accepted_at) {
            return 'en_proceso';
        }
        return 'pendiente';
    }
}

