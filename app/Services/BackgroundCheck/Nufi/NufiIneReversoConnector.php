<?php

namespace App\Services\BackgroundCheck\Nufi;

use App\Models\Subject;
use Illuminate\Support\Facades\Storage;

class NufiIneReversoConnector extends NufiConnector
{
    public function getIdentifier(): string
    {
        return 'ine_reverso';
    }

    public function getName(): string
    {
        return 'Identificación INE Reverso (OCR)';
    }

    public function getMinTierLevel(): int
    {
        return 2;
    }

    public function appliesTo(Subject $subject): bool
    {
        // Applies to physical persons who uploaded back INE image
        return $subject->tipo === 'persona_fisica' && !empty($subject->ine_back_path);
    }

    protected function callApi(Subject $subject): array
    {
        if (empty($subject->ine_back_path)) {
            throw new \Exception("No hay archivo cargado para el reverso de la INE.");
        }

        if (!Storage::exists($subject->ine_back_path)) {
            throw new \Exception("El archivo del reverso de la INE no existe en el almacenamiento.");
        }

        $content = Storage::get($subject->ine_back_path);
        if ($content === false) {
            throw new \Exception("No se pudo leer el archivo del reverso de la INE.");
        }

        $base64 = base64_encode($content);
        $response = $this->postRequest('/ocr/v4/reverso', [
            'base64_credencial_reverso' => $base64
        ]);

        $data = $response['body']['data']['ocr']
            ?? $response['body']['data']
            ?? $response['data']['ocr']
            ?? $response['data']
            ?? $response;

        if (is_array($data)) {
            if (isset($data['ocr']) && empty($data['codigo_ocr'])) {
                $data['codigo_ocr'] = $data['ocr'];
            }
            if (isset($data['numero_identificador']) && empty($data['cic'])) {
                $data['cic'] = $data['numero_identificador'];
            }
        }

        return is_array($data) ? $data : [];
    }

    protected function mockResponse(Subject $subject): array
    {
        $cic = (string)rand(100000000, 999999999);
        $ocr = "IDMEX" . rand(100000000, 999999999) . "<<" . rand(1000, 9999) . "<<<<<<<<<<";
        return [
            'cic' => $cic,
            'codigo_ocr' => $ocr,
            'numero_identificador' => $cic,
        ];
    }

    protected function getMockBody(Subject $subject): array
    {
        if (empty($subject->ine_back_path)) {
            return [];
        }

        try {
            if (\Illuminate\Support\Facades\Storage::exists($subject->ine_back_path)) {
                $content = \Illuminate\Support\Facades\Storage::get($subject->ine_back_path);
                if ($content !== false) {
                    return [
                        'base64_credencial_reverso' => base64_encode($content)
                    ];
                }
            }
        } catch (\Throwable $e) {
            // Fallback to static mock string on error/test fake filesystem
        }

        return [
            'base64_credencial_reverso' => 'mock_base64_string_representing_back_ine'
        ];
    }
}
