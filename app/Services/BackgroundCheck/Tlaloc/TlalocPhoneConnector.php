<?php

namespace App\Services\BackgroundCheck\Tlaloc;

use App\Models\Subject;
use App\Services\BackgroundCheck\BaseSourceConnector;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Conector de Inteligencia Telefónica PNN (Plan Nacional de Numeración) vía Tlaloc.sh.
 *
 * Endpoint Oficial: GET https://api.tlaloc.sh/mx/v1/phone?number={10_digitos}
 * Header: Authorization: Bearer tlmx_...
 */
class TlalocPhoneConnector extends BaseSourceConnector
{
    protected array $lastLog = [];

    public function getIdentifier(): string
    {
        return 'tlaloc_phone';
    }

    public function getName(): string
    {
        return 'Tlaloc PNN (Teléfono / Carrier / Localidad)';
    }

    public function getMinTierLevel(): int
    {
        return 1;
    }

    public function appliesTo(Subject $subject): bool
    {
        return !empty($this->extractMexican10Digits($subject->phone));
    }

    public function getLastLog(): array
    {
        return $this->lastLog;
    }

    public function execute(Subject $subject): array
    {
        $digits = $this->extractMexican10Digits($subject->phone);
        $apiKey = config('background_check.tlaloc.api_key', '');
        $isMock = (bool) config('background_check.tlaloc.mock', false) || empty($apiKey);

        if ($isMock) {
            $mock = $this->mockResponse($digits);
            $this->lastLog = [
                'request'  => ['mode' => 'mock', 'number' => $digits],
                'response' => $mock,
            ];
            return $mock;
        }

        $baseUrl = rtrim(config('background_check.tlaloc.base_url', 'https://api.tlaloc.sh/mx'), '/');
        $url = "{$baseUrl}/v1/phone";

        $this->lastLog = [
            'url'    => "{$url}?number={$digits}",
            'method' => 'GET',
            'headers' => [
                'Authorization' => 'Bearer ***' . substr($apiKey, -4),
                'Accept'        => 'application/json',
            ],
        ];

        try {
            $response = Http::timeout(20)
                ->withToken($apiKey)
                ->acceptJson()
                ->get($url, ['number' => $digits]);

            $body = $response->json() ?? [];
            $this->lastLog['response'] = [
                'status' => $response->status(),
                'body'   => $body,
            ];

            if (!$response->successful()) {
                throw new \RuntimeException($body['detail'] ?? $body['message'] ?? "Tlaloc API error ({$response->status()})");
            }

            return $this->normalizar($digits, is_array($body) ? $body : []);
        } catch (\Throwable $e) {
            Log::error("TlalocPhoneConnector error: " . $e->getMessage());
            $this->lastLog['error'] = $e->getMessage();

            return [
                'numero'    => $digits,
                'encontrado'=> false,
                'registros' => [],
                'mensaje'   => 'Error consultando Tlaloc Phone API: ' . $e->getMessage(),
            ];
        }
    }

    private function extractMexican10Digits(?string $phone): string
    {
        if (empty($phone)) {
            return '';
        }
        $digits = preg_replace('/\D/', '', $phone);
        if (strlen($digits) === 12 && str_starts_with($digits, '52')) {
            $digits = substr($digits, 2);
        } elseif (strlen($digits) === 13 && str_starts_with($digits, '521')) {
            $digits = substr($digits, 3);
        }
        return strlen($digits) === 10 ? $digits : '';
    }

    private function normalizar(string $digits, array $items): array
    {
        $first = $items[0] ?? null;
        if (!is_array($first)) {
            return [
                'numero'     => $digits,
                'encontrado' => false,
                'registros'  => [],
                'mensaje'    => 'Número no localizado en el Plan Nacional de Numeración.',
            ];
        }

        return [
            'numero'           => $digits,
            'encontrado'       => true,
            'poblacion'        => $first['poblacion'] ?? null,
            'municipio'        => $first['municipio'] ?? null,
            'estado'           => $first['estado'] ?? null,
            'clave_censal'     => $first['clave_censal'] ?? null,
            'carrier'          => $first['razon_social'] ?? null,
            'tipo_red'         => $first['tipo_red'] ?? null,
            'modalidad'        => $first['modalidad'] ?? null,
            'nir'              => $first['nir'] ?? null,
            'serie'            => $first['serie'] ?? null,
            'fecha_asignacion' => $first['fecha_asignacion'] ?? null,
            'registros'        => $items,
            'mensaje'          => 'Información PNN obtenida vía Tlaloc.',
        ];
    }

    private function mockResponse(string $digits): array
    {
        return [
            'numero'           => $digits,
            'encontrado'       => true,
            'poblacion'        => 'CIUDAD DE MÉXICO',
            'municipio'        => 'CUAUHTÉMOC',
            'estado'           => 'CIUDAD DE MÉXICO',
            'clave_censal'     => '090150001',
            'carrier'          => 'RADIOMOVIL DIPSA, S.A. DE C.V.',
            'tipo_red'         => 'MOVIL',
            'modalidad'        => 'MPP',
            'nir'              => (int) substr($digits, 0, 2),
            'serie'            => (int) substr($digits, 2, 4),
            'fecha_asignacion' => '2018-04-12',
            'registros'        => [],
            'mensaje'          => '[MOCK] Información PNN simulada (configura TLALOC_API_KEY en .env para modo real).',
        ];
    }
}
