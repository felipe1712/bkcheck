<?php

namespace App\Services\BackgroundCheck\Nufi;

use App\Models\Subject;

/**
 * Conector de Score Crediticio / Buró de Crédito.
 *
 * Consulta el historial crediticio del sujeto a través de la integración
 * NuFi con el Buró de Crédito (personas físicas) o Círculo de Crédito
 * (personas morales).
 *
 * ⚠️  AVISO LEGAL IMPORTANTE:
 * La Ley para Regular las Sociedades de Información Crediticia (LRSIC) y
 * la LFPDPPP exigen AUTORIZACIÓN EXPRESA Y ESPECÍFICA del titular para
 * consultar su historial crediticio. La autorización genérica de consentimiento
 * NO es suficiente — se requiere el campo `credit_consent_granted = true`.
 *
 * Aplica a personas físicas con consentimiento crediticio explícito.
 * Endpoint NuFi esperado: POST /buro/consulta o equivalente.
 */
class NufiFinancialConnector extends NufiConnector
{
    public function getIdentifier(): string
    {
        return 'score_crediticio';
    }

    public function getName(): string
    {
        return 'Score Crediticio / Buró de Crédito';
    }

    /**
     * Solo aplica cuando:
     * 1. El sujeto tiene consentimiento crediticio explícito.
     * 2. Tiene RFC y CURP (requeridos por el Buró de Crédito).
     */
    public function appliesTo(Subject $subject): bool
    {
        return $subject->tipo === 'persona_fisica'
            && !empty($subject->credit_consent_granted)
            && !empty($subject->rfc)
            && !empty($subject->curp);
    }

    protected function callApi(Subject $subject): array
    {
        $response = $this->postRequest('/buro/consulta', [
            'rfc'                  => strtoupper($subject->rfc),
            'curp'                 => strtoupper($subject->curp),
            'primer_apellido'      => $subject->primer_apellido ?? '',
            'segundo_apellido'     => $subject->segundo_apellido ?? '',
            'nombre'               => $subject->primer_nombre ?? $subject->name_or_company,
            'fecha_nacimiento'     => $subject->fecha_nacimiento ?? '',
            'credit_consent_at'    => $subject->credit_consent_at?->toIso8601String(),
        ]);

        return $this->normalizar($response);
    }

    protected function mockResponse(Subject $subject): array
    {
        return [
            'score_buro'          => 680,
            'rango_score'         => 'Regular',   // Excelente > 750, Bueno 700-749, Regular 650-699, Malo < 650
            'nivel_riesgo'        => 'Medio',
            'cuentas_activas'     => 3,
            'cuentas_cerradas'    => 2,
            'cuentas_en_mora'     => 0,
            'monto_total_deuda'   => 45000.00,
            'monto_vencido'       => 0.00,
            'max_atraso_meses'    => 1,
            'consultas_recientes' => 2,
            'fecha_consulta'      => now()->format('Y-m-d'),
            'buro'                => 'Buró de Crédito',
            'tiene_alertas'       => false,
            'alertas'             => [],
            'mensaje'             => '[MOCK] Score crediticio consultado exitosamente.',
            'aviso_legal'         => 'Consulta realizada con autorización expresa del titular bajo consentimiento registrado en fecha ' . ($subject->credit_consent_at?->format('d/m/Y') ?? now()->format('d/m/Y')) . '.',
        ];
    }

    protected function getMockBody(Subject $subject): array
    {
        return [
            'rfc'  => strtoupper($subject->rfc  ?? ''),
            'curp' => strtoupper($subject->curp ?? ''),
        ];
    }

    private function normalizar(array $response): array
    {
        return [
            'score_buro'          => $response['score_buro']          ?? null,
            'rango_score'         => $response['rango_score']         ?? null,
            'nivel_riesgo'        => $response['nivel_riesgo']        ?? null,
            'cuentas_activas'     => $response['cuentas_activas']     ?? null,
            'cuentas_cerradas'    => $response['cuentas_cerradas']    ?? null,
            'cuentas_en_mora'     => $response['cuentas_en_mora']     ?? null,
            'monto_total_deuda'   => $response['monto_total_deuda']   ?? null,
            'monto_vencido'       => $response['monto_vencido']       ?? null,
            'max_atraso_meses'    => $response['max_atraso_meses']    ?? null,
            'consultas_recientes' => $response['consultas_recientes'] ?? null,
            'fecha_consulta'      => $response['fecha_consulta']      ?? null,
            'buro'                => $response['buro']                ?? null,
            'tiene_alertas'       => $response['tiene_alertas']       ?? false,
            'alertas'             => $response['alertas']             ?? [],
            'mensaje'             => $response['mensaje']             ?? null,
            'aviso_legal'         => $response['aviso_legal']         ?? null,
        ];
    }
}
