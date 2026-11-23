<?php

namespace App\Services\BackgroundCheck\Nufi;

use App\Models\Subject;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NufiRfcConnector extends NufiConnector
{
    public function getIdentifier(): string
    {
        return 'rfc';
    }

    public function getName(): string
    {
        return 'Validación de RFC';
    }

    public function appliesTo(Subject $subject): bool
    {
        // RFC validation applies to all subjects
        return !empty($subject->rfc);
    }

    protected function callApi(Subject $subject): array
    {
        // 1. Primera opción: Tlaloc.sh ($0.10 MXN / consulta)
        $tlalocKey = config('background_check.tlaloc.api_key', '');
        if (!empty($tlalocKey) && !config('background_check.tlaloc.mock', false)) {
            $tlalocResult = $this->callTlalocRfc($subject, $tlalocKey);
            if ($tlalocResult !== null) {
                return $tlalocResult;
            }
        }

        // 2. Fallback automático: NuFi
        $response = $this->postRequest('/estatusrfc/valida', [
            'rfc' => $subject->rfc,
        ]);

        $message = $response['message'] ?? '';
        $isValid = stripos($message, 'válido') !== false || stripos($message, 'valido') !== false;

        return [
            'rfc'          => $subject->rfc,
            'valido'       => $isValid,
            'situacion'    => $isValid ? 'ACTIVO' : 'INACTIVO',
            'razon_social' => $subject->name_or_company,
            'tipo_persona' => strlen($subject->rfc) === 12 ? 'MORAL' : 'FISICA',
            'detalle'      => $message,
            'proveedor'    => !empty($tlalocKey) ? 'nufi_fallback' : 'nufi',
        ];
    }

    private function callTlalocRfc(Subject $subject, string $apiKey): ?array
    {
        $baseUrl = rtrim(config('background_check.tlaloc.base_url', 'https://api.tlaloc.sh/mx'), '/');
        $url     = "{$baseUrl}/v1/rfc";
        $params  = ['rfc' => strtoupper(trim($subject->rfc))];

        if (!empty($subject->name_or_company) && !str_starts_with($subject->name_or_company, 'Por resolver')) {
            $params['nombre_razon_social'] = $subject->name_or_company;
        }

        try {
            $response = Http::timeout(15)
                ->withToken($apiKey)
                ->acceptJson()
                ->get($url, $params);

            $body = $response->json();

            if ($response->successful() && is_array($body) && array_key_exists('valid', $body)) {
                $this->lastLog = [
                    'provider' => 'tlaloc',
                    'url'      => $url,
                    'method'   => 'GET',
                    'query'    => $params,
                    'response' => [
                        'status' => $response->status(),
                        'body'   => $body,
                    ],
                ];

                $isValid = (bool) $body['valid'];

                return [
                    'rfc'          => $subject->rfc,
                    'valido'       => $isValid,
                    'acepta_cfdi'  => (bool) ($body['accept_cfdi'] ?? false),
                    'situacion'    => $isValid ? 'ACTIVO' : 'INACTIVO',
                    'razon_social' => $subject->name_or_company,
                    'tipo_persona' => strlen($subject->rfc) === 12 ? 'MORAL' : 'FISICA',
                    'detalle'      => $body['reason'] ?? '',
                    'proveedor'    => 'tlaloc',
                ];
            }

            Log::warning("Tlaloc RFC retornó HTTP {$response->status()}; ejecutando fallback NuFi.");
        } catch (\Throwable $e) {
            Log::warning("Tlaloc RFC falló ({$e->getMessage()}); ejecutando fallback NuFi.");
        }

        return null;
    }

    protected function mockResponse(Subject $subject): array
    {
        return [
            'rfc' => $subject->rfc,
            'valido' => true,
            'situacion' => 'ACTIVO',
            'razon_social' => $subject->name_or_company,
            'tipo_persona' => $subject->tipo === 'persona_moral' ? 'MORAL' : 'FISICA',
            'curp' => $subject->curp ?? ($subject->tipo === 'persona_fisica' ? 'MOCKCURP840502HDFLNR01' : null),
        ];
    }
}
