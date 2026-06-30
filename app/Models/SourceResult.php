<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SourceResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_query_id',
        'raw_payload',
        'processed_data',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'processed_data' => 'array',
    ];

    /**
     * Get the query that owns this result.
     */
    public function sourceQuery()
    {
        return $this->belongsTo(SourceQuery::class, 'source_query_id');
    }
}
