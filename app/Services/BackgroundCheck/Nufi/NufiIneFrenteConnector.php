<?php

namespace App\Services\BackgroundCheck\Nufi;

use App\Models\Subject;
use Illuminate\Support\Facades\Storage;

class NufiIneFrenteConnector extends NufiConnector
{
    public function getIdentifier(): string
    {
        return 'ine_frente';
    }

    public function getName(): string
    {
        return 'Identificación INE Frente (OCR)';
    }

    public function getMinTierLevel(): int
    {
        return 2;
    }

    public function appliesTo(Subject $subject): bool
    {
        // Applies to physical persons who uploaded front INE image
        return $subject->tipo === 'persona_fisica' && !empty($subject->ine_front_path);
    }

    protected function callApi(Subject $subject): array
    {
        if (empty($subject->ine_front_path)) {
            throw new \Exception("No hay archivo cargado para el frente de la INE.");
        }

        if (!Storage::exists($subject->ine_front_path)) {
            throw new \Exception("El archivo del frente de la INE no existe en el almacenamiento.");
        }

        $content = Storage::get($subject->ine_front_path);
        if ($content === false) {
            throw new \Exception("No se pudo leer el archivo del frente de la INE.");
        }

        $base64 = base64_encode($content);
        $response = $this->postRequest('/ocr/v4/frente', [
            'base64_credencial_frente' => $base64
        ]);

        return $response['data'] ?? $response ?? [];
    }

    protected function mockResponse(Subject $subject): array
    {
        $nameParts = explode(' ', $subject->name_or_company);
        $nombre = $nameParts[0] ?? 'JUAN';
        $paterno = $nameParts[1] ?? 'PÉREZ';
        $materno = $nameParts[2] ?? 'LÓPEZ';

        return [
            'nombre' => strtoupper($nombre),
            'apellido_paterno' => strtoupper($paterno),
            'apellido_materno' => strtoupper($materno),
            'curp' => $subject->curp ?: 'PELJ900101HDFLNR01',
            'clave_elector' => 'PRLPJN' . rand(10, 99) . rand(10, 99) . rand(10, 99) . '09H' . rand(100, 999),
            'numero_emision' => '01',
            'seccion' => (string)rand(1000, 9999),
            'fecha_nacimiento' => '1990-01-01',
            'sexo' => 'H',
            'vigencia' => $subject->ine_front_path ? '2030' : 'N/A',
        ];
    }

    protected function getMockBody(Subject $subject): array
    {
        if (empty($subject->ine_front_path)) {
            return [];
        }

        try {
            if (\Illuminate\Support\Facades\Storage::exists($subject->ine_front_path)) {
                $content = \Illuminate\Support\Facades\Storage::get($subject->ine_front_path);
                if ($content !== false) {
                    return [
                        'base64_credencial_frente' => base64_encode($content)
                    ];
                }
            }
        } catch (\Throwable $e) {
            // Fallback to static mock string on error/test fake filesystem
        }

        return [
            'base64_credencial_frente' => 'mock_base64_string_representing_front_ine'
        ];
    }
}
