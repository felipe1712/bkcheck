<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory, HasTenant;

    // Disable default timestamps, we only need created_at
    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'subject_name',
        'subject_rfc',
        'fuente',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Get the user that executed the query.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
