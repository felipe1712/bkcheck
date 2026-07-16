<?php

namespace App\Services\BackgroundCheck\Nufi;

use App\Models\Subject;

/**
 * Conector de Selfie / Prueba de Vida.
 *
 * Este conector registra la selfie del investigado y la prepara para
 * ser enviada al endpoint de prueba de vida de NuFi.
 *
 * TODO: Conectar con el endpoint de prueba de vida de NuFi cuando
 * esté disponible y cableado en la API key de producción.
 * Endpoint esperado: POST /v1/liveness o equivalente según docs de NuFi.
 */
class NufiSelfieConnector extends NufiConnector
{
    public function getIdentifier(): string
    {
        return 'selfie';
    }

    public function getName(): string
    {
        return 'Prueba de Vida (Selfie)';
    }

    /**
     * Solo aplica a personas físicas que tienen selfie subida.
     */
    public function appliesTo(Subject $subject): bool
    {
        return $subject->tipo === 'persona_fisica' && !empty($subject->selfie_path);
    }

    /**
     * Ejecuta la verificación. Por ahora retorna mock hasta que NuFi habilite el endpoint.
     */
    protected function callApi(Subject $subject): array
    {
        // TODO: Cuando el endpoint NuFi de liveness esté cableado, reemplazar con:
        // 1. Leer $subject->selfie_path y convertir a base64.
        // 2. Hacer POST al endpoint de NuFi con { 'base64_selfie': '...' }.
        // 3. Retornar la respuesta normalizada.
        return $this->mockResponse($subject);
    }

    protected function mockResponse(Subject $subject): array
    {
        return [
            'status'            => 'selfie_recibida',
            'mensaje'           => '[MOCK] Selfie recibida. Verificación de prueba de vida pendiente de integración con NuFi.',
            'selfie_disponible' => !empty($subject->selfie_path),
            'pendiente_nufi'    => true,
        ];
    }
}
