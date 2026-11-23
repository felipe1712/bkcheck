<?php

namespace App\Services\BackgroundCheck;

use App\Models\Subject;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Conector Dark Web OSINT — Robin (apurvsinghgautam/robin) con Anthropic + Tor.
 */
class RobinDarkWebConnector extends BaseSourceConnector
{
    protected array $lastLog = [];

    public function getIdentifier(): string
    {
        return 'robin_darkweb';
    }

    public function getName(): string
    {
        return 'Robin Dark Web OSINT (Anthropic + Tor)';
    }

    public function getMinTierLevel(): int
    {
        return 3;
    }

    public function appliesTo(Subject $subject): bool
    {
        return !empty($subject->name_or_company)
            || !empty($subject->email)
            || !empty($subject->rfc);
    }

    public function getLastLog(): array
    {
        return $this->lastLog;
    }

    public function execute(Subject $subject): array
    {
        $isMock = !config('background_check.osint.enabled', false)
            || !config('background_check.osint.robin_enabled', false);

        if ($isMock) {
            $mock = $this->getMockResponse($subject);
            $this->lastLog = [
                'request'  => ['mode' => 'mock', 'subject_id' => $subject->id],
                'response' => $mock,
            ];
            return $mock;
        }

        $serviceUrl = config('background_check.osint.service_url', 'http://127.0.0.1:5001');
        $secret     = config('background_check.osint.secret', 'bkcheck-osint-dev-secret');
        $model      = config('background_check.osint.robin.model', 'claude-sonnet-4-20250514');

        $terms = array_filter([
            $subject->name_or_company,
            $subject->email,
            $subject->rfc,
        ]);
        $query = implode(' OR ', $terms);

        $payload = [
            'query' => $query,
            'model' => $model,
        ];

        $this->lastLog['request'] = [
            'url'     => $serviceUrl . '/osint/robin',
            'payload' => $payload,
        ];

        try {
            $response = Http::timeout(200)
                ->withHeaders(['X-OSINT-Secret' => $secret])
                ->post($serviceUrl . '/osint/robin', $payload);

            $data = $response->json() ?? [];
            $this->lastLog['response'] = $data;

            if (!$response->successful()) {
                throw new \RuntimeException($data['error'] ?? 'Error en Robin Dark Web OSINT.');
            }

            return $data;
        } catch (\Throwable $e) {
            Log::error('RobinDarkWebConnector error: ' . $e->getMessage());
            $this->lastLog['error'] = $e->getMessage();

            return [
                'fuente'     => 'robin',
                'status'     => 'unavailable',
                'modelo_llm' => $model,
                'resumen'    => '',
                'hallazgos'  => [],
                'mensaje'    => 'Servicio Robin OSINT no disponible: ' . $e->getMessage(),
            ];
        }
    }

    private function getMockResponse(Subject $subject): array
    {
        return [
            'fuente'     => 'robin',
            'status'     => 'completed',
            'modelo_llm' => config('background_check.osint.robin.model', 'claude-sonnet-4-20250514'),
            'query'      => $subject->name_or_company ?? $subject->email ?? $subject->rfc,
            'resumen'    => '[MOCK] Sin menciones ni filtraciones detectadas en índices Dark Web (.onion) consultados vía Tor + Anthropic.',
            'hallazgos'  => [],
        ];
    }
}
