<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PldFuenteResultado extends Model
{
    use HasFactory;

    protected $table = 'pld_fuente_resultado';

    protected $fillable = [
        'folio',
        'fuente',
        'categoria',
        'criticidad',
        'status_http',
        'resultado_crudo',
        'fecha_consulta',
        'ultima_consulta_exitosa',
        'estado',
    ];

    protected $casts = [
        'status_http'              => 'integer',
        'resultado_crudo'          => 'array',
        'fecha_consulta'           => 'datetime',
        'ultima_consulta_exitosa'  => 'datetime',
    ];
}
