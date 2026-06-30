<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SourceQuery extends Model
{
    use HasFactory, HasTenant;

    protected $fillable = [
        'tenant_id',
        'subject_id',
        'source_type',
        'status',
        'error_message',
    ];

    /**
     * Get the subject that this query belongs to.
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the tenant that owns the query.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the result associated with the query.
     */
    public function result()
    {
        return $this->hasOne(SourceResult::class, 'source_query_id');
    }
}
