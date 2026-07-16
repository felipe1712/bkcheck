<?php

namespace App\Services\BackgroundCheck\Nufi;

use App\Models\Subject;
use Illuminate\Support\Facades\Storage;

/**
 * Conector de Comprobante de Domicilio (OCR).
 *
 * Envía la imagen del comprobante de domicilio (CFE, Telmex, agua, etc.)
 * al motor OCR de NuFi para extraer y validar los datos de domicilio
 * del sujeto investigado.
 *
 * Aplica a personas físicas que han subido un comprobante de domicilio.
 * Endpoint NuFi esperado: POST /domicilio/ocr o equivalente multipart.
 */
class NufiDomicilioConnector extends NufiConnector
{
    public function getIdentifier(): string
    {
        return 'comprobante_domicilio';
    }

    public function getName(): string
    {
        return 'Comprobante de Domicilio (OCR)';
    }

    public function appliesTo(Subject $subject): bool
    {
        return $subject->tipo === 'persona_fisica'
            && !empty($subject->proof_of_address_path);
    }

    protected function callApi(Subject $subject): array
    {
        if (!Storage::exists($subject->proof_of_address_path)) {
            throw new \Exception('El archivo de comprobante de domicilio no existe en el almacenamiento.');
        }

        $fileContent = Storage::get($subject->proof_of_address_path);
        $base64      = base64_encode($fileContent);
        $mimeType    = Storage::mimeType($subject->proof_of_address_path) ?? 'image/jpeg';

        $response = $this->postRequest('/domicilio/ocr', [
            'imagen'      => $base64,
            'tipo_imagen' => $mimeType,
            'rfc'         => $subject->rfc,
        ]);

        return $this->normalizar($response);
    }

    protected function mockResponse(Subject $subject): array
    {
        return [
            'valido'              => true,
            'tipo_comprobante'    => 'CFE',
            'titular'             => $subject->name_or_company,
            'calle'               => 'Av. Insurgentes Sur',
            'numero_exterior'     => '1234',
            'numero_interior'     => 'Depto. 5',
            'colonia'             => 'Del Valle',
            'municipio'           => 'Benito Juárez',
            'estado'              => 'Ciudad de México',
            'codigo_postal'       => '03100',
            'fecha_emision'       => now()->subMonths(1)->format('Y-m-d'),
            'periodo_facturado'   => now()->subMonths(1)->format('Y-m'),
            'coincide_con_sujeto' => true,
            'confianza'           => 0.97,
            'mensaje'             => '[MOCK] Comprobante de domicilio validado correctamente por OCR.',
        ];
    }

    protected function getMockBody(Subject $subject): array
    {
        return ['rfc' => $subject->rfc, 'archivo' => 'proof_of_address.jpg'];
    }

    private function normalizar(array $response): array
    {
        return [
            'valido'              => $response['valido']              ?? false,
            'tipo_comprobante'    => $response['tipo_comprobante']    ?? null,
            'titular'             => $response['titular']             ?? null,
            'calle'               => $response['calle']               ?? null,
            'numero_exterior'     => $response['numero_exterior']     ?? null,
            'numero_interior'     => $response['numero_interior']     ?? null,
            'colonia'             => $response['colonia']             ?? null,
            'municipio'           => $response['municipio']           ?? null,
            'estado'              => $response['estado']              ?? null,
            'codigo_postal'       => $response['codigo_postal']       ?? null,
            'fecha_emision'       => $response['fecha_emision']       ?? null,
            'periodo_facturado'   => $response['periodo_facturado']   ?? null,
            'coincide_con_sujeto' => $response['coincide_con_sujeto'] ?? null,
            'confianza'           => $response['confianza']           ?? null,
            'mensaje'             => $response['mensaje']             ?? null,
        ];
    }
}
