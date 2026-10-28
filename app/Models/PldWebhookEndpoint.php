<?php

namespace App\Models;

use App\Services\Pld\PldCryptoService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PldWebhookEndpoint extends Model
{
    use HasFactory;

    protected $table = 'pld_webhook_endpoints';

    protected $fillable = [
        'tenant_id',
        'url',
        'secreto',
        'eventos_suscritos',
        'incluir_sin_cambios',
        'activo',
    ];

    protected $casts = [
        'eventos_suscritos'   => 'array',
        'incluir_sin_cambios' => 'boolean',
        'activo'              => 'boolean',
    ];

    public function setSecretoAttribute(?string $value): void
    {
        if (empty($value)) {
            $this->attributes['secreto'] = '';
            return;
        }
        $this->attributes['secreto'] = app(PldCryptoService::class)->encrypt($value);
    }

    public function getDecryptedSecretoAttribute(): string
    {
        if (empty($this->attributes['secreto'])) {
            return '';
        }
        try {
            return (string) app(PldCryptoService::class)->decrypt($this->attributes['secreto']);
        } catch (\Throwable $e) {
            return (string) $this->attributes['secreto'];
        }
    }
}
