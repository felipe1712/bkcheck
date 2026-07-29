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

        $rawBody = $response['body'] ?? $response;
        $rawData = $rawBody['data'] ?? $rawBody;
        $ocrData = is_array($rawData['ocr'] ?? null) ? $rawData['ocr'] : [];

        $mrz = '';
        if (isset($ocrData['mrz'])) {
            $mrz = $ocrData['mrz'];
        } elseif (isset($ocrData['ocr'])) {
            $mrz = $ocrData['ocr'];
        } elseif (isset($rawData['mrz'])) {
            $mrz = $rawData['mrz'];
        } elseif (isset($rawData['codigo_ocr'])) {
            $mrz = $rawData['codigo_ocr'];
        } elseif (isset($rawData['ocr']) && is_string($rawData['ocr'])) {
            $mrz = $rawData['ocr'];
        }

        $cic = $rawData['cic'] 
            ?? $rawData['numero_identificador'] 
            ?? $ocrData['cic'] 
            ?? $ocrData['numero_identificador'] 
            ?? null;

        // Extraer automáticamente el número CIC de 9 a 10 dígitos del código MRZ si no viene explícito
        if (empty($cic) && !empty($mrz)) {
            if (preg_match('/IDMEX(\d{9,10})/i', $mrz, $matches)) {
                $cic = $matches[1];
            }
        }

        return [
            'codigo_ocr'           => $mrz ?: 'N/A',
            'mrz'                  => $mrz ?: 'N/A',
            'cic'                  => $cic ?: 'N/A',
            'numero_identificador' => $cic ?: 'N/A',
            'model'                => $ocrData['model'] ?? $rawData['model'] ?? 'N/A',
            'raw_ocr'              => $ocrData,
        ];
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
