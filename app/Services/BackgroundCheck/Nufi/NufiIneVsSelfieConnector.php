<?php

namespace App\Services\BackgroundCheck\Nufi;

use App\Models\Subject;
use Illuminate\Support\Facades\Storage;

class NufiIneVsSelfieConnector extends NufiConnector
{
    public function getIdentifier(): string
    {
        return 'ine_vs_selfie';
    }

    public function getName(): string
    {
        return 'Comparación Biométrica Facial (INE vs Selfie / Rostro)';
    }

    public function getMinTierLevel(): int
    {
        return 2;
    }

    public function appliesTo(Subject $subject): bool
    {
        return $subject->tipo === 'persona_fisica';
    }

    protected function callApi(Subject $subject): array
    {
        $frenteB64 = '';
        if ($subject->ine_front_path && Storage::exists($subject->ine_front_path)) {
            $frenteB64 = base64_encode(Storage::get($subject->ine_front_path));
        }

        $reversoB64 = '';
        if ($subject->ine_back_path && Storage::exists($subject->ine_back_path)) {
            $reversoB64 = base64_encode(Storage::get($subject->ine_back_path));
        }

        $selfieB64 = '';
        if ($subject->selfie_path && Storage::exists($subject->selfie_path)) {
            $selfieB64 = base64_encode(Storage::get($subject->selfie_path));
        }

        // Dummy transparent 1x1 image fallback if file missing in testing/dev
        $dummyB64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $body = [
            'imagen_rostro'      => $selfieB64 ?: $dummyB64,
            'credencial_frente'  => $frenteB64 ?: $dummyB64,
            'credencial_reverso' => $reversoB64 ?: $dummyB64,
        ];

        $response = $this->postRequest('/biometrico/v2/ine_vs_selfie', $body);

        $rawBody = $response['body'] ?? $response;
        $statusStr = $rawBody['status'] ?? 'success';
        $messageStr = $rawBody['message'] ?? 'Consulta con exito!';
        $data = $rawBody['data'] ?? [];

        $coincide = strtolower((string)($data['resultado_verificacion_rostro'] ?? '')) === 'true' || ($data['resultado_verificacion_rostro'] ?? '') === '1';
        $certezaRaw = (float)($data['certeza_verificacion_rostro'] ?? 0);
        $certezaPorcentaje = number_format($certezaRaw * 100, 2) . '%';
        $frenteValido = ($data['resultado_credencial_frente'] ?? '') === '1';
        $reversoValido = ($data['resultado_credencial_reverso'] ?? '') === '1';

        return [
            'coincide_rostro'           => $coincide,
            'certeza'                   => $certezaRaw,
            'certeza_porcentaje'        => $certezaPorcentaje,
            'frente_valido'             => $frenteValido,
            'reverso_valido'            => $reversoValido,
            'tipo_credencial_frente'    => $data['tipo_credencial_frente'] ?? 'N/A',
            'tipo_credencial_reverso'   => $data['tipo_credencial_reverso'] ?? 'N/A',
            'uuid'                      => $rawBody['uuid'] ?? '',
            'status'                    => $statusStr,
            'message'                   => $messageStr,
            'detalles'                  => $data,
        ];
    }

    protected function mockResponse(Subject $subject): array
    {
        return [
            'coincide_rostro'           => true,
            'certeza'                   => 0.9485,
            'certeza_porcentaje'        => '94.85%',
            'frente_valido'             => true,
            'reverso_valido'            => true,
            'tipo_credencial_frente'    => 'INE / IFE',
            'tipo_credencial_reverso'   => 'INE / IFE',
            'uuid'                      => 'fb183f6f-942d-4796-898e-6286be6b7e2d',
            'status'                    => 'success',
            'message'                   => 'Consulta con exito!',
            'detalles'                  => [
                'resultado_verificacion_rostro' => 'True',
                'certeza_verificacion_rostro'   => '0.94850',
                'resultado_credencial_frente'   => '1',
                'tipo_credencial_frente'        => 'INE / IFE',
                'resultado_credencial_reverso'  => '1',
                'tipo_credencial_reverso'       => 'INE / IFE',
            ]
        ];
    }

    protected function getMockBody(Subject $subject): array
    {
        return [
            'imagen_rostro'      => '[BASE64_SELFIE]',
            'credencial_frente'  => '[BASE64_INE_FRENTE]',
            'credencial_reverso' => '[BASE64_INE_REVERSO]',
        ];
    }
}
