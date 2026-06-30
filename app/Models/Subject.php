<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'tenant_id',
    ];

    protected $casts = [
        'consent_granted' => 'boolean',
        'consent_date' => 'datetime',
        'rfc' => 'encrypted',
        'curp' => 'encrypted',
        'address' => 'encrypted',
    ];

    /**
     * Get the project that the subject belongs to.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
