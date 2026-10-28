<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PldFuenteCatalogo extends Model
{
    use HasFactory;

    protected $table = 'pld_fuente_catalogo';

    protected $fillable = [
        'fuente_key',
        'nombre',
        'categoria',
        'criticidad',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Siembra el catálogo global por defecto desde config('pld.catalogo_default').
     */
    public static function ensureDefaultsSeeded(): void
    {
        $defaults = config('pld.catalogo_default', []);
        foreach ($defaults as $key => $row) {
            static::firstOrCreate(
                ['fuente_key' => $key],
                [
                    'nombre'     => $row['nombre'],
                    'categoria'  => $row['categoria'],
                    'criticidad' => $row['criticidad'],
                    'activo'     => $row['activo'] ?? true,
                ]
            );
        }
    }
}
