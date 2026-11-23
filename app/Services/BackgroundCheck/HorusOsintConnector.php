<?php

namespace App\Services\BackgroundCheck;

use App\Models\Subject;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Conector OSINT Horus (6abd/horus).
 *
 * Ejecuta inteligencia sobre teléfono (carrier, región, validez) y
 * correo electrónico / dominio (MX, Gravatar, dominio corporativo) mediante
 * el microservicio Python aislado.
 */
class HorusOsintConnector extends BaseSourceConnector
{
    protected array $lastLog = [];

    public function getIdentifier(): string
    {
        return 'horus_osint';
    }

    public function getName(): string
    {
        return 'Horus OSINT (Teléfono y Correo/Dominio)';
    }

    public function getMinTierLevel(): int
    {
        return 2;
    }

    public function appliesTo(Subject $subject): bool
    {
        return !empty($subject->phone) || !empty($subject->email);
    }

    public function getLastLog(): array
    {
        return $this->lastLog;
    }

    public function execute(Subject $subject): array
    {
        $isMock = !config('background_check.osint.enabled', false)
            || !config('background_check.osint.horus_enabled', false);

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

        $payload = [
            'nombre' => $subject->name_or_company ?? '',
            'phone'  => $subject->phone ?? '',
            'email'  => $subject->email ?? '',
        ];

        $this->lastLog['request'] = [
            'url'     => $serviceUrl . '/osint/horus',
            'payload' => $payload,
        ];

        try {
            $response = Http::timeout(60)
                ->withHeaders(['X-OSINT-Secret' => $secret])
                ->post($serviceUrl . '/osint/horus', $payload);

            $data = $response->json() ?? [];
            $this->lastLog['response'] = $data;

            if (!$response->successful()) {
                throw new \RuntimeException($data['error'] ?? 'Error en Horus OSINT.');
            }

            return $data;
        } catch (\Throwable $e) {
            Log::error('HorusOsintConnector error: ' . $e->getMessage());
            $this->lastLog['error'] = $e->getMessage();

            return [
                'fuente'      => 'horus',
                'phone_intel' => null,
                'email_intel' => null,
                'mensaje'     => 'Servicio Horus OSINT no disponible: ' . $e->getMessage(),
            ];
        }
    }

    private function getMockResponse(Subject $subject): array
    {
        return [
            'fuente'      => 'horus',
            'phone_intel' => !empty($subject->phone) ? [
                'raw'       => $subject->phone,
                'valido'    => true,
                'e164'      => $subject->phone,
                'region'    => 'Ciudad de México, México',
                'carrier'   => 'Telcel (Radiomóvil Dipsa)',
                'timezones' => ['America/Mexico_City'],
            ] : null,
            'email_intel' => !empty($subject->email) ? [
                'email'          => $subject->email,
                'dominio'        => str_contains($subject->email, '@') ? explode('@', $subject->email)[1] : '',
                'mx_valido'      => true,
                'mx_records'     => ['alt1.gmail-smtp-in.l.google.com'],
                'gravatar_url'   => null,
                'es_corporativo' => false,
            ] : null,
            'mensaje' => '[MOCK] Enriquecimiento Horus OSINT ejecutado.',
        ];
    }
}
